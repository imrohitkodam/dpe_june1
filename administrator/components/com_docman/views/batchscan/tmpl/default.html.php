<?

/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die; ?>

<script data-inline type="text/javascript">
    if (typeof Docman === 'undefined') {
        Docman = {};
    }

    Docman.scannerData = <?= json_encode($scannerData) ?>;
    Docman.documentUrl = '<?= route('&view=document', true, false); ?>';
    Docman.documentsUrl = '<?= route('&view=documents', true, false); ?>';
    Docman.categoryUrl = '<?= route('&view=category', true, false); ?>';
    Docman.fileUrl = '<?= route('&view=file&routed=1&container=docman-files', true, false); ?>';
    Docman.baseUrl = '<?= route('&format=json', true, false); ?>';
</script>
<script src="https://api.joomlatools.com/scanner/static/batchscan-head-joomla-1.0.0?docmanVersion=<?= $docmanVersion ?>"></script>
<script data-inline type="module" src="https://api.joomlatools.com/scanner/static/batchscan-module-joomla-1.0.0?docmanVersion=<?= $docmanVersion ?>"></script>
