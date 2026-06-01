<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Installer\Installer;
use Joomla\Component\Installer\Administrator\Model\UpdatesitesModel;

class WatchfulliExtensionUpdater
{
    /** @var WatchfulliAppSwitcher */
    private $appSwitcher;
    /** @var CMSApplication */
    private $application;

    public function __construct()
    {
        $this->application = Factory::getApplication();
        $this->appSwitcher = new WatchfulliAppSwitcher();
    }

    /**
     * @param stdClass $extParams
     * @return array
     * @throws Exception
     */
    public function update($extParams)
    {
        Watchfulli::debug("WatchfulliExtensionUpdater::doInstall - starting");

        $context = [
            'extParams' => $extParams,
        ];

        if (empty($extParams->update_url)) {
            return [
                'task' => 'install',
                'status' => 'error',
                'message' => 'COM_JMONITORING_CANT_GET_UPDATE_URL',
            ];
        }

        $file = $this->downloadFile($extParams);

        if (!$file) {
            $message = $extParams->is_paid && !$extParams->has_valid_license_key ? 'COM_JMONITORING_PRO_NOT_AVAILABLE' : 'COM_JMONITORING_CANT_DOWNLOAD_UPDATE';
            return [
                'task' => 'install',
                'status' => 'error',
                'message' => $message,
                'context' => $context,
            ];
        }

        try {
            $package = $this->unpackFile($file, $extParams->package_name);
        } catch (Exception $ex) {
            $context['exception'] = $ex->getMessage();
            $context['exceptionTrace'] = $ex->getTraceAsString();

            return [
                'task' => 'install',
                'status' => 'exception',
                'message' => 'COM_JMONITORING_CANT_UNPACK_UPDATE',
                'context' => $context,
            ];
        }
        Watchfulli::debug("WatchfulliExtensionUpdater::doInstall - file unpacked: $file");

        $installer = Installer::getInstance();

        $this->appSwitcher->switchToWatchfulApp();

        Watchfulli::debug("WatchfulliExtensionUpdater::doInstall - switched to Watchful app");

        if ($installer->install($package['dir']) === false && !Factory::getApplication()->installStatus) {
           $context['installStatus'] = Factory::getApplication()->installStatus;

            return [
                'task' => 'install',
                'status' => 'error',
                'message' => 'COM_JMONITORING_CANT_INSTALL_UPDATE',
                'context' => $context,
            ];
        }

        Watchfulli::debug("WatchfulliExtensionUpdater::doInstall - package installed");

        $this->appSwitcher->switchToOriginalApp();

        InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

        $message = "ok_".$installer->manifest->version;

        return [
            'task' => 'install',
            'status' => 'success',
            'message' => $message,
        ];
    }

    /**
     * Unpack a given file
     *
     * @param string $file the name of the file to unpack
     *
     * @return array   a package object
     * @throws Exception
     */
    private function unpackFile($file, $packageName)
    {
        if (!$file) {
            throw new Exception('COM_JMONITORING_CANT_UNPACK_UPDATE_EMPTY_FILE');
        }

        $tmp_path = Factory::getConfig()->get('tmp_path', JPATH_SITE.'/tmp');
        if (!file_exists($tmp_path)) {
            throw new Exception('COM_JMONITORING_CANT_UNPACK_UPDATE_WRONG_TMP_PATH');
        }

        if ($packageName && ($file != $packageName)) {
            File::move($tmp_path.'/'.$file, $tmp_path.'/'.$packageName);
            $file = $packageName;
        }

        if (!file_exists($tmp_path.'/'.$file)) {
            throw new Exception('COM_JMONITORING_CANT_UNPACK_UPDATE_MISSING_FILE');
        }

        $package = InstallerHelper::unpack($tmp_path.'/'.$file);
        if (empty($package)) {
            throw new Exception('COM_JMONITORING_CANT_UNPACK_UPDATE');
        }

        return $package;
    }

    private function downloadFile($extParams)
    {
        $localDownloadKeyUrl = $this->getLocalDownloadKeyUrl($extParams);
        Watchfulli::debug("WatchfulliExtensionUpdater::downloadFile - localDownloadKeyUrl: $localDownloadKeyUrl");

        $file = InstallerHelper::downloadPackage($localDownloadKeyUrl ?: $extParams->update_url, $extParams->package_name);
        Watchfulli::debug("WatchfulliExtensionUpdater::doInstall - package downloaded");

        if (!$file && $localDownloadKeyUrl) {
            Watchfulli::debug("WatchfulliExtensionUpdater::doInstall - package download failed");
            $file = InstallerHelper::downloadPackage($extParams->update_url, $extParams->package_name);
        }

        return $file;
    }

    private function getLocalDownloadKeyUrl($extParams)
    {
        if (version_compare(Watchfulli::joomla()->getShortVersion(), '4.0', '<')) {
            return false;
        }

        try {
            $key = $this->getDownloadKey($extParams);
        } catch (Exception $e) {
            return false;
        }

        if (!$key) {
            return false;
        }

        if (
            empty($key['supported']) ||
            empty($key['valid']) ||
            empty($key['prefix']) ||
            empty($key['value'])
        ) {
            return false;
        }

        if (substr($key['prefix'], -1) !== '=') {
            return false;
        }

        $param = substr($key['prefix'], 0, -1);

        $url = parse_url($extParams->update_url);
        $url['query'] = $url['query'] ?? '';

        parse_str($url['query'], $query);

        $value = $key['value'] . ($key['suffix'] ?? '');

        $query[$param] = $value;

        $url['query'] = http_build_query($query);

        return $url['scheme'] . '://' . $url['host'] . $url['path'] . '?' . $url['query'];
    }

    private function getDownloadKey($extParams)
    {
        $updates = (new UpdatesitesModel())->getItems();

        $updatesWithKey = array_filter($updates, function ($update) use ($extParams) {
            return $update->element === $extParams->ext_prefix && !empty($update->downloadKey) && $update->downloadKey['supported'] && $update->downloadKey['valid'];
        });

        if (empty($updatesWithKey)) {
            return false;
        }

        $update = reset($updatesWithKey);

        return $update->downloadKey;
    }
}