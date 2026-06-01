<?php

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class SeAiChatLicenseChecker
{
    const ACTIVATION_FILE = '.license_activated';

    // Feature cache so we only read the file once per request
    private static ?array $featureCache = null;

    private static function getFilePath(): string
    {
        return JPATH_ADMINISTRATOR . '/components/com_seaichat/' . self::ACTIVATION_FILE;
    }

    private static function getServerUrl(): string
    {
        $params = ComponentHelper::getParams('com_seaichat');
        return rtrim($params->get('license_server', 'https://se24media.co.uk'), '/');
    }

    private static function getLicenseKey(): string
    {
        $params = ComponentHelper::getParams('com_seaichat');
        return trim($params->get('license_key', ''));
    }

    private static function getDomain(): string
    {
        return Uri::getInstance()->getHost();
    }

    private static function maskKey(string $key): string
    {
        if (strlen($key) <= 7) return $key;
        return substr($key, 0, 7) . '••••••••••••';
    }

    private static function httpGet(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            ]);
            $body  = curl_exec($ch);
            $http  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errno = curl_errno($ch);
            curl_close($ch);

            // SSL fallback
            if (($body === false || $http === 0) && in_array($errno, [60, 77, 35])) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 20,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
                ]);
                $body = curl_exec($ch);
                curl_close($ch);
            }

            if ($body !== false) return $body;
        }

        $ctx  = stream_context_create(['http' => ['timeout' => 20, 'header' => 'Accept: application/json']]);
        $body = @file_get_contents($url, false, $ctx);
        return $body !== false ? $body : null;
    }

    // -------------------------------------------------------------------------
    // Core activation state
    // -------------------------------------------------------------------------

    public static function isActivated(): bool
    {
        $file = self::getFilePath();
        if (!file_exists($file)) return false;

        $data = json_decode(file_get_contents($file), true);
        if (empty($data['activated']) || empty($data['license_key'])) return false;

        $currentKey = self::getLicenseKey();
        if (!empty($currentKey) && $data['license_key'] !== $currentKey) return false;

        return true;
    }

    // -------------------------------------------------------------------------
    // NEW: read features from the activation file
    // -------------------------------------------------------------------------

    /**
     * Returns the raw features array stored at activation time.
     * Example: ["tier:pro", "max_kb_sources:unlimited", "premium_models", ...]
     */
    public static function getFeatures(): array
    {
        if (self::$featureCache !== null) {
            return self::$featureCache;
        }

        $file = self::getFilePath();
        if (!file_exists($file)) {
            self::$featureCache = [];
            return [];
        }

        $data = json_decode(file_get_contents($file), true) ?: [];
        self::$featureCache = $data['features'] ?? [];
        return self::$featureCache;
    }

    /**
     * Quick helper: returns true if a specific feature tag is present.
     * e.g. self::hasFeature('premium_models')
     */
    public static function hasFeature(string $feature): bool
    {
        $features = self::getFeatures();
        foreach ($features as $f) {
            // Match exact tag OR "tag:value" prefix
            if ($f === $feature || strpos($f, $feature . ':') === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the numeric value of a feature like "max_kb_sources:3".
     * Returns -1 (unlimited) if the value is "unlimited" or if not found and $defaultUnlimited is true.
     */
    public static function getFeatureLimit(string $feature, int $freeDefault): int
    {
        $features = self::getFeatures();
        foreach ($features as $f) {
            if (strpos($f, $feature . ':') === 0) {
                $val = substr($f, strlen($feature) + 1);
                if ($val === 'unlimited') return -1;
                return (int) $val;
            }
        }
        return $freeDefault;
    }

    /**
     * True if the activated license is Pro tier.
     */
    public static function isPro(): bool
    {
        if (!self::isActivated()) return false;
        return self::hasFeature('tier:pro');
    }

    /**
     * True if the activated license is Free tier (or no activation file yet).
     */
    public static function isFree(): bool
    {
        return !self::isPro();
    }

    /**
     * Returns the upgrade URL — used in all upgrade prompts.
     */
    public static function upgradeUrl(): string
    {
        $params = ComponentHelper::getParams('com_seaichat');
        $server = rtrim($params->get('license_server', 'https://se24media.co.uk'), '/');
        return $server . '/component/subseller/se-ai-chatbot';
    }

    // -------------------------------------------------------------------------
    // Activation / deactivation (unchanged logic, extended to save features)
    // -------------------------------------------------------------------------

    public static function activate(): array
    {
        $serverUrl = self::getServerUrl();
        $key       = self::getLicenseKey();
        $domain    = self::getDomain();

        if (empty($serverUrl)) return ['success' => false, 'error' => 'No license server URL configured. Go to Options to set it.'];
        if (empty($key))       return ['success' => false, 'error' => 'No license key entered. Go to Options to enter your key.'];

        // Step 1: Validate
        $validateUrl = $serverUrl . '/index.php?option=com_subseller&task=api.validate&license_key=' . urlencode($key);
        $response    = self::httpGet($validateUrl);

        if ($response === null) {
            return ['success' => false, 'error' => 'Cannot connect to the license server. Please check the server URL and try again.'];
        }

        $data = json_decode($response, true);
        if (empty($data) || !isset($data['valid'])) {
            return ['success' => false, 'error' => 'Invalid response from the license server.'];
        }

        if (!$data['valid']) {
            return ['success' => false, 'error' => $data['error'] ?? 'License key is not valid.'];
        }

        // Step 2: Activate domain (best-effort)
        $activateUrl = $serverUrl . '/index.php?option=com_subseller&task=api.activate&license_key=' . urlencode($key) . '&domain=' . urlencode($domain);
        self::httpGet($activateUrl);

        // Step 3: Parse license_features from the validation response
        // SubSeller returns license_features as a dedicated array separate from the marketing features
        $features = [];
        if (!empty($data['license_features']) && is_array($data['license_features'])) {
            $features = $data['license_features'];
        }

        // Step 4: Write activation file (now includes features)
        $activationData = [
            'activated'      => true,
            'license_key'    => $key,
            'activated_date' => date('Y-m-d H:i:s'),
            'domain'         => $domain,
            'plan_title'     => $data['plan'] ?? '',
            'expires_at'     => $data['expires_at'] ?? '',
            'status'         => $data['status'] ?? 'active',
            'features'       => $features,
        ];

        $file = self::getFilePath();
        if (@file_put_contents($file, json_encode($activationData, JSON_PRETTY_PRINT)) === false) {
            return ['success' => false, 'error' => 'Cannot write activation file. Check folder permissions for: ' . dirname($file)];
        }

        // Clear the in-memory cache so isPro() picks up the new data immediately
        self::$featureCache = null;

        // Step 5: Sync update site key
        self::syncUpdateSiteKey($key);

        return ['success' => true, 'data' => $activationData];
    }

    public static function deactivate(): void
    {
        $file = self::getFilePath();
        if (file_exists($file)) {
            @unlink($file);
        }
        self::$featureCache = null;
    }

    public static function getStatus(): array
    {
        $serverUrl = self::getServerUrl();
        $key       = self::getLicenseKey();

        if (!empty($key)) {
            self::syncUpdateSiteKey($key);
        }

        if (empty($serverUrl)) {
            return ['state' => 'error', 'message' => 'No license server URL configured.', 'can_activate' => false];
        }
        if (empty($key)) {
            return ['state' => 'error', 'message' => 'No license key entered. Go to Options to enter your license key.', 'can_activate' => false];
        }
        if (!self::isActivated()) {
            return ['state' => 'error', 'message' => 'License not yet activated. Click Activate License to activate.', 'can_activate' => true, 'masked_key' => self::maskKey($key)];
        }

        $file = self::getFilePath();
        $data = json_decode(file_get_contents($file), true) ?: [];

        $expiresAt = $data['expires_at'] ?? '';
        $isExpired = false;
        if (!empty($expiresAt)) {
            $isExpired = (strtotime($expiresAt) < time());
        }

        return [
            'state'          => $isExpired ? 'expired' : 'active',
            'message'        => '',
            'can_activate'   => false,
            'masked_key'     => self::maskKey($key),
            'plan_title'     => $data['plan_title'] ?? '',
            'expires_at'     => $expiresAt,
            'activated_date' => $data['activated_date'] ?? '',
            'domain'         => $data['domain'] ?? '',
            'features'       => $data['features'] ?? [],
            'is_pro'         => self::isPro(),
        ];
    }

    public static function syncUpdateSiteKey(?string $key = null): void
    {
        if ($key === null) {
            $key = self::getLicenseKey();
        }
        if (empty($key)) return;

        try {
            $db = Factory::getContainer()->get('DatabaseDriver');

            foreach (['pkg_seaichat', 'com_seaichat'] as $element) {
                $type  = ($element === 'pkg_seaichat') ? 'package' : 'component';
                $query = $db->getQuery(true)
                    ->select('us.update_site_id')
                    ->from($db->quoteName('#__update_sites', 'us'))
                    ->join('INNER', $db->quoteName('#__update_sites_extensions', 'use') . ' ON us.update_site_id = use.update_site_id')
                    ->join('INNER', $db->quoteName('#__extensions', 'e') . ' ON use.extension_id = e.extension_id')
                    ->where($db->quoteName('e.element') . ' = ' . $db->quote($element))
                    ->where($db->quoteName('e.type') . ' = ' . $db->quote($type));
                $db->setQuery($query);
                $updateSiteId = $db->loadResult();

                if ($updateSiteId) {
                    $query = $db->getQuery(true)
                        ->update($db->quoteName('#__update_sites'))
                        ->set($db->quoteName('extra_query') . ' = ' . $db->quote('license_key=' . $key))
                        ->where($db->quoteName('update_site_id') . ' = ' . (int) $updateSiteId);
                    $db->setQuery($query);
                    $db->execute();
                    return;
                }
            }
        } catch (\Exception $e) {
            // Non-fatal
        }
    }
}
