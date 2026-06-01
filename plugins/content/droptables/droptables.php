<?php
/**
 * Droptables
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and to customize.
 * Otherwise, please feel free to contact us at contact@joomunited.com *
 *
 * @package   Droptables
 * @copyright Copyright (C) 2014 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @copyright Copyright (C) 2014 Damien Barrère (http://www.crac-design.com). All rights reserved.
 * @license   GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */


//-- No direct access
defined('_JEXEC') || die('=;)');


jimport('joomla.plugin.plugin');
jimport('joomla.application.categories');
jimport('joomla.filesystem.file');

/**
 * Class plgContentdroptables
 */
class plgContentdroptables extends JPlugin  //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps -- Class name not change
{
    /**
     * Check the first get function
     *
     * @var integer
     */
    public static $Once = 1;

    /**
     * Example before display content method
     * Method is called by the view and the results are imploded and displayed in a placeholder
     *
     * @param string  $context    The context for the content passed to the plugin.
     * @param object  $article    The content object.  Note $article->text is also available
     * @param object  $params     The content params
     * @param integer $limitstart The 'page' number
     *
     * @return boolean
     */
    public function onContentPrepare($context, $article, $params = array(), $limitstart = 0)
    {
        $this->context = $context;

        $article->text = preg_replace_callback('@<img[^>]*?data\-droptablestable="([0-9]+)".*?>@', array($this, 'replace'), $article->text);

        /*
         * Sync page code use cUrl
         *
         * */
        $componentParams = JComponentHelper::getParams('com_droptables');
        $sync_periodicity = (int)$componentParams->get('sync_periodicity');
        if (!empty($sync_periodicity)) {
            $curSyncInterval = $this->curSyncInterval($componentParams);

            if ($curSyncInterval >= $sync_periodicity && self::$Once === 1) {
                $doc = JFactory::getDocument();
                JHtml::_('jquery.framework');
                $script = "jQuery(document).ready(function(){
                        jQuery.ajax({
                            url: '" . JUri::root() . "index.php?option=com_droptables&task=frontexcel.syncSpreadsheet',
                            type: 'POST',
                            success: function (datas) {
                            },
                            error: function (jqxhr, textStatus, error) {
                            },
                        });
                    });";
                $doc->addScriptDeclaration($script);

                self::$Once = 0;
            }
        }
        return true;
    }

    /**
     * Function curSyncInterval
     *
     * @param object $params Component params
     *
     * @return integer
     */
    private function curSyncInterval($params)
    {
        //get last_log param
        if ($params->get('last_sync') !== null) {
            $last_log = $params->get('last_sync');
            $time_old = (int)$last_log;
        } else {
            $time_old = 0;
        }

        $time_new = (int)strtotime(date('Y-m-d H:i:s'));
        $timeInterval = $time_new - $time_old;
        $curtime = $timeInterval / 60;

        return $curtime;
    }

    /**
     * Convert the table content to front end
     *
     * @param array $match Match string
     *
     * @return string|array|boolean
     */
    private function replace($match)
    {
        $lang = JFactory::getLanguage();
        $lang->load('com_droptables', JPATH_ADMINISTRATOR . '/components/com_droptables', null, true);
        $lang->load('com_droptables.override', JPATH_ADMINISTRATOR . '/components/com_droptables', null, true);
        $lang->load('com_droptables.sys', JPATH_ADMINISTRATOR . '/components/com_droptables', null, true);

        JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_droptables/models/', 'DroptablesModel');
        JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_droptables/tables/', 'DroptablesTablesTable');
        require_once('chartStyleSet.php');

        $version = '3.6.5';

        $exp = '@<img.*data\-droptables\-chart="([0-9]+)".*?>@';
        preg_match($exp, $match[0], $matches);
        require_once(JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_droptables' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'table.php');
        $droptablesModelTableBackend = new DroptablesModelTable();
        if (count($matches) > 0) { //is a chart
            $content = '';
            JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_droptables/models/', 'DroptablesModel');
            $model = JModelLegacy::getInstance('Chart', 'droptablesModel');

            $chart = $model->getChart($matches[1]);
            // get table config
            $modelTable = JModelLegacy::getInstance('Table', 'droptablesModel');
            if ($chart) {
                $table = $droptablesModelTableBackend->getItem($chart->id_table, true, true, false, false);
                $params = JComponentHelper::getParams('com_droptables');
                if (empty($table) || empty($table->data)) {
                    return '';
                }

                $datas = is_string($table->data) ? json_decode($table->data) : $table->data;
                $table_config = is_string($table->style) ? json_decode($table->style) : $table->style;
                $tblParams = $table->params;

                $chartConfig = json_decode($chart->config);
                if (isset($chartConfig->useFirstRowAsGraph)) {
                    $this->useFirstRowAsGraph = $chartConfig->useFirstRowAsGraph;
                }

                $chartData = $this->getChartData($chart->datas, $datas);

                $currency_symbol = (empty($table_config->table->currency_symbol))
                    ? $params->get('default_currency_symbol', '$')
                    : $table_config->table->currency_symbol;
                $unit_symbols = (empty($table_config->table->unit_symbols))
                    ? $params->get('default_symbol_position', '0')
                    : $table_config->table->unit_symbols;
                $places = (empty($table_config->table->places))
                    ? $params->get('default_decimal_place', '0')
                    : $table_config->table->places;
                $decimal_symbols = (empty($table_config->table->decimal_symbols))
                    ? $params->get('default_decimal_symbol', '.')
                    : $table_config->table->decimal_symbols;
                $thousand_symbols = (empty($table_config->table->thousand_symbols))
                    ? $params->get('default_thousand_symbol', ',')
                    : $table_config->table->thousand_symbols;

                $jsChartData = $this->buildJsChartData(
                    $chartData,
                    $chart->type,
                    $chartConfig,
                    $currency_symbol,
                    $thousand_symbols,
                    $decimal_symbols
                );
                if (!$chartConfig) {
                    $chartConfig = new stdClass();
                }
                $chartConfig->width = isset($chartConfig->width) ? $chartConfig->width : 500;
                $chartConfig->height = isset($chartConfig->height) ? $chartConfig->height : 375;
                $chartConfig->chart_align = isset($chartConfig->chart_align) ? $chartConfig->chart_align : 'center';
                $symbol = '';
                $countChartData = count($chartData);
                for ($i = 0; $i < $countChartData; $i++) {
                    $countChartDataI = count($chartData[$i]);
                    for ($j = 0; $j < $countChartDataI; $j++) {
                        if (strpos($chartData[$i][$j], $currency_symbol) !== false) {
                            $chartData[$i][$j] = str_replace($thousand_symbols, '', $chartData[$i][$j]);
                            $chartData[$i][$j] = str_replace($decimal_symbols, '.', $chartData[$i][$j]);
                            if (is_numeric(str_replace($currency_symbol, '', $chartData[$i][$j]))) {
                                $symbol = $currency_symbol;
                                break;
                            }
                        }
                    }
                }
                $js = 'var DropChart = {};' . "\n";
                $reactVar = new stdClass();
                $js .= 'DropChart.id = "' . $chart->id . '" ; ' . "\n";
                $reactVar->id = $chart->id;
                $js .= 'DropChart.type = "' . $chart->type . '" ; ' . "\n";
                $reactVar->type = $chart->type;
                $js .= 'DropChart.data = ' . $jsChartData . '; ' . "\n";
                $reactVar->data = $jsChartData;
                $js .= 'DropChart.currency_symbol = "' . $symbol . '"; ' . "\n";
                $reactVar->currency_symbol = $symbol;
                $js .= 'DropChart.places = ' . $places . '; ' . "\n";
                $reactVar->places = $places;
                $js .= 'DropChart.unit_symbols = ' . $unit_symbols . '; ' . "\n";
                $reactVar->unit_symbols = $unit_symbols;
                $js .= 'DropChart.decimal_symbols = "' . $decimal_symbols . '"; ' . "\n";
                $reactVar->decimal_symbols = $decimal_symbols;
                $js .= 'DropChart.thousand_symbols = "' . $thousand_symbols . '"; ' . "\n";
                $reactVar->thousand_symbols = $thousand_symbols;

                if (isset($chartConfig->useFirstRowAsGraph)) {
                    $reactVar->useFirstRowAsGraph = $chartConfig->useFirstRowAsGraph;
                    $js .= 'DropChart.useFirstRowAsGraph = "' . $reactVar->useFirstRowAsGraph . '"; ' . "\n";
                }

                if ($chart->config) {
                    $js .= 'DropChart.config = ' . $chart->config . '; ' . "\n";
                    $reactVar->config = $chart->config;
                } else {
                    $js .= 'DropChart.config = {} ; ' . "\n";
                    $reactVar->config = new stdClass();
                }
                $js .= ' if(typeof DropCharts === "undefined") { var DropCharts = []; } ; ' . "\n";

                $js .= ' DropCharts.push(DropChart) ; ' . "\n";

                JHtml::_('jquery.framework');
                $document = JFactory::getDocument();
                $document->addScript(JUri::base() . 'components/com_droptables/assets/js/chart.min.js');
                $document->addScriptDeclaration($js);
                $document->addScript(JUri::base() . 'components/com_droptables/assets/js/dropchart.js?v=' . $version);

                $content = '<div class="chartContainer droptables" id="chartContainer' . $chart->id . '" data-id-chart="' . $chart->id . '">';
                $align = '';
                switch ($chartConfig->chart_align) {
                    case 'left':
                        $align = ' margin : 0 auto 0 0; ';
                        break;
                    case 'right':
                        $align = ' margin : 0 0 0 auto ';
                        break;
                    case 'none':
                    case 'center':
                    default:
                        $align = ' margin : 0 auto 0 auto ';
                        break;
                }

                $content .= '<div class="canvasWraper" style="max-height:' . $chartConfig->height
                    . 'px; max-width:' . $chartConfig->width . 'px;' . $align . '" >';
                $content .= '<canvas class="canvas"  height="' . $chartConfig->height . '" width="' . $chartConfig->width . '"></canvas>';
                $content .= '</div></div>';
//                $content .= '<script>' . $js . '</script>';

                return $content;
            }
        } else {
            JHtml::_('jquery.framework');
            $document = JFactory::getDocument();
//            $model = JModelLegacy::getInstance('Table', 'droptablesModel');
            $table = $droptablesModelTableBackend->getItem($match[1], true, true, true);

            $configParams = JComponentHelper::getParams('com_droptables');

            if (empty($table) || empty($table->data)) {
                return '';
            }

            $datas = is_string($table->data) ? json_decode($table->data) : $table->data;
            $style = is_string($table->style) ? json_decode($table->style) : $table->style;
            $params = $table->params;
            $table_type = !empty($table->type) ? $table->type : 'html';

            $hightLight = !isset($configParams['highlighting_enable']) ? 0 : (int)$configParams['highlighting_enable'];
            $table->hightlight_color = !isset($configParams['highlighting_color']) ? '#ffffaa' : $configParams['highlighting_color'];
            $table->hightlight_font_color = !isset($configParams['highlighting_color_text']) ? '#ffffff' : $configParams['highlighting_color_text'];
            $table->hightlight_opacity = !isset($configParams['highlighting_opacity']) ? '0.9' : $configParams['highlighting_opacity'];
            $default_order_sortable = isset($style->table->default_order_sortable) ? (int)$style->table->default_order_sortable : 0;
            $default_sort = isset($style->table->default_sortable) ? (int)$style->table->default_sortable : 0;
            $enable_pagination = isset($style->table->enable_pagination) ? (int)$style->table->enable_pagination : 0;

            //todo: usergroup use?
//            $table_usergroups = isset($style->table->usergroup) ? $style->table->usergroup : array();
//            if (!empty($table_usergroups) && !in_array('1', $table_usergroups)) {
//                $user = JFactory::getUser();
//                $groups = $user->get('groups');
//                $result = array_intersect($table_usergroups, $groups);
//                if (empty($result)) {
//                    return '';
//                }
//            }

            if (!isset($style->table) || count((array)$style->table) < 1) {
                $style->table = new stdClass();
            }
            if (!isset($style->table->freeze_col)) {
                $style->table->freeze_col = 0;
            }
            if (!isset($style->table->freeze_row)) {
                $style->table->freeze_row = 0;
            }
            if (!isset($style->table->enable_filters)) {
                $style->table->enable_filters = 0;
            }
            $sortable = false;
            if (isset($style->table->use_sortable) && (int)$style->table->use_sortable === 1) {
                $sortable = true;
            }
            $responsive_type = 'scroll';
            if (isset($style->table->responsive_type) && (string)$style->table->responsive_type === 'hideCols') {
                $responsive_type = 'hideCols';
            }
            if (!isset($style->table->enable_filters)) {
                $style->table->enable_filters = false;
            }
            if (!isset($style->table->table_height)) {
                $style->table->table_height = 500;
            }

            $content = '';
            $table->id = $match[1];
            $table->sortable = $sortable;
            $table->enable_filters = $style->table->enable_filters;
            $data_style = $this->styleRender($table);

            $document = JFactory::getDocument();
            $document->addStyleSheet(JUri::base() . 'media/droptables/' . JFile::makeSafe($table->id . '_' . $table->hash . '.css'));
            $document->addStyleSheet(JURI::root() . 'components/com_droptables/assets/css/front.css?t=1');
            $document->addStyleSheet(JURI::root() . 'components/com_droptables/assets/DataTables/datatables.min.css');
            $document->addStyleSheet(JURI::root() . 'components/com_droptables/assets/tipso/tipso.css');
            if (isset($table->data) && $table->data !== null && !empty($table->data) || $enable_pagination) {
                JHtml::_('jquery.framework');

                // hightlight
                if ((int)$hightLight < 1) {
                    $table->hightlight_color = 'not hightlight';
                }

                $document->addScript(JURI::base() . 'components/com_droptables/assets/js/moment.min.js');
                $document->addScript(JURI::base() . 'components/com_droptables/assets/js/moment-jdateformatparser.js');
                $document->addScript(JUri::base() . 'components/com_droptables/assets/DataTables/datatables.min.js');

                /* add tipso lib when tooltip cell exists*/
                $document->addScript(JUri::base() . 'components/com_droptables/assets/tipso/tipso.js');

                $document->addScript(JUri::base() . 'components/com_droptables/assets/js/jquery.fileDownload.js?v=' . $version);
                $document->addScript(JUri::base() . 'components/com_droptables/assets/js/front.js?v=' . $version);

                $check_sortable = '';
                $content .= '<div class="droptables_table tablesorter-bootstrap ' . $check_sortable . '" data-id="' . (int)$table->id . '" data-hightlight="' . $hightLight . '">';
                /*button download table*/
                if (isset($params->download_button) && $params->download_button) {
                    $content .= '<input type="button" data-href="#" href="javascript:void(0);" class="download_droptables" value="' . JText::_('COM_DROPTABLES_DOWNLOAD_TABLE') . '"/>';
                }
                $limit = isset($style->table->limit_rows) ? (int)$style->table->limit_rows : 0;
                $tableContent = $this->getTableContent($table, true, $data_style);

                if ($tableContent) {
                    $content .= $tableContent;

                    // Simple performance check to email cloak
                    if (strpos($tableContent, 'joomla-hidden-mail') !== false) {
                        JLoader::register('DroptablesBase', JPATH_ADMINISTRATOR . '/components/com_droptables/classes/droptablesBase.php');
                        if (droptablesBase::isJoomla40()) {
                            JFactory::getDocument()->getWebAssetManager()
                                ->useScript('webcomponent.hidden-mail');
                        }
                    }
                }
                //phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_html401Found -- Not support php version 5.4 or earlier
                $content = html_entity_decode($content, ENT_COMPAT | ENT_HTML401, 'UTF-8');

                $content .= '<script>droptables_ajaxurl = "' . JUri::root() . 'index.php?option=com_droptables";</script>';

                $content .= '</div><style>' . $this->hightLightCss . '</style>';
            }

            return $content;
        }

        return false;
    }

    /**
     * Get table Html Content
     *
     * @param object  $table      Table object
     * @param boolean $getData    Get table datas
     * @param array   $data_style Style value
     *
     * @return string|boolean
     */
    public function getTableContent($table, $getData, $data_style)
    {
        $params = $table->params;

        JLoader::register('DroptablesHelper', JPATH_ADMINISTRATOR . '/components/com_droptables/helpers/droptables.php');

        $configParams = JComponentHelper::getParams('com_droptables');

        if (!empty($data_style['data'])) {
            $valueTable = DroptablesHelper::htmlRender($table, $configParams, $data_style['data'], $table->hash, $getData);
        } else {
            $valueTable = DroptablesHelper::htmlRender($table, $configParams, array(), $table->hash, $getData);
        }

        if ((isset($params->table_type) && $params->table_type === 'html') || $table->type === 'html') {
            $folder = JPATH_SITE . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'droptables' . DIRECTORY_SEPARATOR;
            $file = $folder . JFile::makeSafe($table->id . '_' . $table->hash . '.html');

            $content = file_get_contents($file);
            return $content;
        } elseif (is_string($valueTable) && $valueTable !== '') {
            return $valueTable;
        } else {
            return 'table is empty';
        }

        return false;
    }

    /**
     * Function convert alpha string to integer
     *
     * @param string $col Alpha string
     *
     * @return integer
     */
    private function convertAlpha($col)
    {
        //function from http://www.bradino.com/php/excel-column-convert-letters-to-numbers/
        $col = str_pad($col, 2, '0', STR_PAD_LEFT);
        $i = ($col[0] === '0') ? 0 : (ord($col[0]) - 64) * 26;
        $i += ord($col[1]) - 64;

        return $i;
    }

    /**
     * Render style to front-end
     *
     * @param object $table Table style data
     *
     * @return array
     */
    private function styleRender($table)
    {
        $style = is_string($table->style) ? json_decode($table->style) : $table->style;
        if ($style === null) {
            return array();
        }
        $contents = array();
        $style_tooltip = array();
        $componentParams = JComponentHelper::getParams('com_droptables');

        $hightlight_color = $table->hightlight_color;
        $hightlight_font_color = $table->hightlight_font_color;
        $hightlight_opacity = $table->hightlight_opacity;

        if ($hightlight_color !== 'not hightlight') {
            require_once('chartStyleSet.php');
            $chartStyleObj = new ChartStyleSet($hightlight_color);
            $highlighting_rgbcolor = $chartStyleObj->hex2rgba($hightlight_color, $hightlight_opacity);
            $table->hightlight_css = '.droptables-highlight-horizontal, .droptables-highlight-vertical  {  color: ' . $hightlight_font_color . ' !important; background: ' . $highlighting_rgbcolor . ' !important; }';
        } else {
            $table->hightlight_css = '';
        }
        $this->hightLightCss = $table->hightlight_css;

        $tableContent = 'table {';

        //render global table params
        if (isset($style->table->alternate_row_odd_color) && $style->table->alternate_row_odd_color) {
            $tableContent .= 'tr:nth-child(even) td {background-color: ' . $style->table->alternate_row_odd_color . ';}';
        }
        if (isset($style->table->alternate_row_even_color) && $style->table->alternate_row_even_color) {
            $tableContent .= 'tr:nth-child(odd) td {background-color: ' . $style->table->alternate_row_even_color . ';}';
        }
        if (isset($style->table->row_border) && $style->table->row_border) {
            $tableContent .= 'tr td {border-bottom: ' . $style->table->row_border . ';}';
        }

        if (isset($style->table->headerOption) && (int)$style->table->headerOption > 1) {
            $header_content = array();
            for ($i = 0; $i < $style->table->headerOption; $i++) {
                $header_content[] = !empty($style->header_data) && !empty($style->header_data[$i]) ? $style->header_data[$i] : $table->data[$i];
            }
        } else {
            $header_content = $table->data[0];
        }

        //render global rows
        if (!(!isset($style->table->allRowHeight) || $style->table->allRowHeight === '')) {
            $tableContent .= ' td, th {height: ' . (int)$style->table->allRowHeight . 'px;}';
        }

        if (isset($style->rows)) {
            foreach ($style->rows as $numberRow => $row) {
                $tableContent .= '.dtr' . (int)$numberRow . ' {';

                if (is_object($row) && isset($row->{1}->height)) {
                    $styleRows = $row->{1};
                    $tableContent .= ' height: ' . (int)$row->{1}->height . 'px;';
                } elseif (is_array($row) && isset($row[1]->height)) {
                    $styleRows = $row[1];
                    $tableContent .= ' height: ' . (int)$row[1]->height . 'px;';
                } else {
                    $styleRows = null;
                }

                if ($styleRows !== null) {
                    foreach ($styleRows as $numberStyleRow => $styleRow) {
                        if ($numberStyleRow !== 'height') {
                            $tableContent .= $this->addStyleColsRows(
                                $styleRow,
                                null,
                                null,
                                $numberStyleRow
                            );
                        }
                    }
                }

                $tableContent .= '}';

                //responsive_type repeated header
                if (isset($style->table->responsive_type) && $style->table->responsive_type === 'repeatedHeader') {
                    $tableContent .= ' &.repeatedHeaderTrue tbody .dtr' . (int)$numberRow . ' {';

                    if (is_object($row) && isset($row->{1}->height)) {
                        $tableContent .= ' min-height: ' . (int)$row->{1}->height . 'px;';
                    } elseif (is_array($row) && isset($row[1]->height)) {
                        $tableContent .= ' min-height: ' . (int)$row[1]->height . 'px;';
                    } else {
                        $tableContent .= ' min-height: 30px;';
                    }

                    $tableContent .= ';}';
                }
            }
        }

        if (isset($style->cols)) {
            foreach ($style->cols as $numberCol => $col) {
                if (!empty($col)) {
                    $firstCol = is_object($col) ? $col->{1} : $col[1];
                    $tableContent .= '.dtc' . (int)($numberCol) . ' {';
                    if (isset($firstCol->width)) {
//                        $tableContent .= ' width: ' . (int)$firstCol->width . 'px !important; max-width: ' . (int)$firstCol->width . 'px !important; min-width: ' . (int)$firstCol->width . 'px;';//(commit 694549b40a39c283c2b214de6389fd9420139670 wptm)
                        $tableContent .= ' width: ' . (int)$firstCol->width . 'px; min-width: ' . (int)$firstCol->width . 'px;';
                    }

                    foreach ($firstCol as $numberStyleCol => $styleCol) {
                        if ($numberStyleCol !== 'width') {
                            $tableContent .= $this->addStyleColsRows(
                                $styleCol,
                                null,
                                null,
                                $numberStyleCol
                            );
                        }
                    }
                    $tableContent .= '}';

                    //responsive_type repeated header

                    if (isset($style->table->responsive_type) && $style->table->responsive_type === 'repeatedHeader') {
                        $tableContent .= '&.repeatedHeaderTrue { tbody td:nth-of-type(' . ((int)($numberCol) + 1) . '):before {content:';
                        if (!empty($header_content[(int)($numberCol)])) {
                            if (is_array($header_content[0])) {
                                $tableContent .= '"' . $header_content[0][(int)($numberCol)] . '"';
                            } else {
                                $tableContent .= '"' . $header_content[(int)($numberCol)] . '"';
                            }
                        } else {
                            $tableContent .= '""';
                        }
                        $tableContent .= ';}';
                        $tableContent .= '&:not(.style_repeated) tbody tr.odd td.dtc' . (int)($numberCol) . ' {width: auto !important;border: none !important; border-bottom: 1px solid #ffffff !important;';
                        $tableContent .= ';}';
                        $tableContent .= '&:not(.style_repeated) tbody tr.even td.dtc' . (int)($numberCol) . ' {width: auto !important;border: none !important; border-bottom: 1px solid #eaeaea !important;';
                        $tableContent .= ';}}';
                    }
                }
            }
        }

        if ($table->type === 'mysql') {
            if (isset($style->table->allAlternate)) {
                $tableContent .= 'tbody tr.odd td {background-color: ' . $style->table->allAlternate->even . '}';
                $tableContent .= 'tbody tr.even td {background-color: ' . $style->table->allAlternate->old . '}';
                if (!empty($style->table->allAlternate->header)) {
                    $tableContent .= 'thead tr:first-child th {background-color: ' . $style->table->allAlternate->header . '}';
                }
                if (!empty($style->table->allAlternate->footer)) {
                    $tableContent .= 'tbody tr.footer_class td {background-color: ' . $style->table->allAlternate->footer . '}';
                }
            }
        }

        $content2 = '';
        if (isset($style->table->width) && $style->table->width > 0) {
            $content2 .= '& {width : ' . $style->table->width . 'px;}';
        }

        if (!isset($style->table->table_align)) {
            $style->table->table_align = 'center';
        }

        switch ($style->table->table_align) {
            case 'right':
                $content2 .= '& {margin : 0 0 0 auto}';
                break;
            case 'left':
                $content2 .= '& {margin : 0 auto 0 0}';
                break;
            case 'none':
            case 'center':
            default:
                $content2 .= '& {margin : 0 auto 0 auto}';
                break;
        }
        $content2 .= '}';

        $i = 0;
        $alternateColorValue = !empty($style->table->alternateColorValue) ? $style->table->alternateColorValue : null;
        $content = '';

        foreach ($style->cells as $cell) {
            $rowStyle = null;
            $colStyle = null;
            if (isset($style->rows->{(int)($cell[0])})) {
                $rowStyle = is_object($style->rows->{(int)($cell[0])}) ? $style->rows->{(int)($cell[0])}->{1} : $style->rows->{(int)($cell[0])}[1];
            }
            if (isset($style->cols->{(int)($cell[1])})) {
                $colStyle = is_object($style->cols->{(int)($cell[1])}) ? $style->cols->{(int)($cell[1])}->{1} : $style->cols->{(int)($cell[1])}[1];
            }

            $cell_style_background = '';
            //render global table params
            if ($table->type !== 'mysql' || ($table->type === 'mysql' && empty($style->table->allAlternate))) {
                $AlternateColor = null;
                if (isset($cell[2]['AlternateColor'])) {
                    if (is_object($alternateColorValue) && isset($alternateColorValue->{$cell[2]['AlternateColor']})) {
                        $AlternateColor = $alternateColorValue->{$cell[2]['AlternateColor']};
                    } elseif (is_array($alternateColorValue) && isset($alternateColorValue[$cell[2]['AlternateColor']])) {
                        $AlternateColor = $alternateColorValue[$cell[2]['AlternateColor']];
                    }
                }

                if (isset($AlternateColor)) {
                    $numberRow = 0;
                    if ($AlternateColor->header === '') {
                        $numberRow = -1;
                    }
                    switch ($cell[0]) {
                        case $AlternateColor->selection[0]:
                            if ($AlternateColor->header === '') {
                                $cell_style_background .= 'background-color: ' . $AlternateColor->even . '; ';
                            } else {
                                $cell_style_background .= 'background-color: ' . $AlternateColor->header . '; ';
                            }
                            break;
                        case $AlternateColor->selection[2]:
                            if ($AlternateColor->footer === '') {
                                if (($cell[0] - (int)($AlternateColor->selection[0] + $numberRow)) % 2) {
                                    $cell_style_background .= 'background-color: ' . $AlternateColor->even . '; ';
                                } else {
                                    $cell_style_background .= 'background-color: ' . $AlternateColor->old . '; ';
                                }
                            } else {
                                $cell_style_background .= 'background-color: ' . $AlternateColor->footer . '; ';
                            }
                            break;
                        default:
                            if (($cell[0] - (int)($AlternateColor->selection[0] + $numberRow)) % 2) {
                                $cell_style_background .= 'background-color: ' . $AlternateColor->even . '; ';
                            } else {
                                $cell_style_background .= 'background-color: ' . $AlternateColor->old . '; ';
                            }
                            break;
                    }
                }
            }

            $cell_style = '';
            if ($cell_style_background !== '') {
                if ($table->enable_filters || $table->sortable) {//if user filter|sort then AlternateColor not by row id
                    $content .= '.row_index' . (int)($cell[0]) . ' .dtc' . (int)($cell[1]) . ' {' . $cell_style_background . '}';
                } else {
                    $cell_style .= $cell_style_background;
                }
            }


            $cell_style .= $this->addStyleColsRows(
                isset($colStyle) && isset($colStyle->cell_background_color) ? $colStyle->cell_background_color : null,
                isset($rowStyle) && isset($rowStyle->cell_background_color) ? $rowStyle->cell_background_color : null,
                $cell,
                'cell_background_color'
            );

            if (is_array($cell[2])) {
                foreach ($cell[2] as $keyStyleCell => $styleCell) {
                    if (in_array($keyStyleCell, array(
                        'cell_border_top',
                        'cell_border_right',
                        'cell_border_bottom',
                        'cell_border_left',
                        'cell_font_family',
                        'cell_font_size',
                        'cell_font_color',
                        'cell_font_bold',
                        'cell_font_italic',
                        'cell_font_underline',
                        'cell_text_align',
                        'cell_padding_left',
                        'cell_vertical_align',
                        'cell_padding_top',
                        'cell_padding_right',
                        'cell_padding_bottom',
                        'cell_background_radius_left_top',
                        'cell_background_radius_right_top',
                        'cell_background_radius_right_bottom',
                        'cell_background_radius_left_bottom',
                        'cell_border_bottom'))) {
                        $cell_style .= $this->addStyleColsRows(
                            isset($colStyle) && isset($colStyle->{$keyStyleCell}) ? $colStyle->{$keyStyleCell} : null,
                            isset($rowStyle) && isset($rowStyle->{$keyStyleCell}) ? $rowStyle->{$keyStyleCell} : null,
                            $cell,
                            $keyStyleCell
                        );
                    }
                }
            }

            $content .= '.dtr' . (int)($cell[0]) . '.dtc' . (int)($cell[1]) . ' {' . $cell_style . '}';
            if (isset($cell[2]['tooltip_width']) && !empty($cell[2]['tooltip_width'])) {
                $style_tooltip[(int)($cell[0]) . '_' . (int)($cell[1])] = $cell[2]['tooltip_width'];
            }
            $i++;

            if ($i > 0 && $i % 1000 === 0) {
                $contents[$i / 1000 - 1] = $tableContent . $content . $content2;
                $content = '';
            }
        }

        $count_content = count($contents);
        $contents[$count_content] = $tableContent . $content . $content2;

        JLoader::register('DroptablesHelper', JPATH_ADMINISTRATOR . '/components/com_droptables/helpers/droptables.php');
        DroptablesHelper::compileStyleFrontEnd($table, $contents, $table->css);

        $data_return = array(
            'tooltip' => $style_tooltip
        );

        return array ('data' => $data_return);
    }

    /**
     * Merge style col, row, cell
     *
     * @param string $cols Col  style
     * @param string $rows Row  style
     * @param array  $cell List style
     * @param string $key  Name style
     *
     * @return string
     */
    public function addStyleColsRows($cols, $rows, $cell, $key)
    {
        if (isset($cell) && isset($cell[2][$key]) && false !== $cell[2][$key]) {
            $value = $cell[2][$key];
        } elseif (isset($rows)) {
            $value = $rows;
        } elseif (isset($cols)) {
            $value = $cols;
        }
        $content = '';
        if (isset($value)) {
            switch ($key) {
                case 'cell_background_color':
                    if ($value !== '') {
                        $content = ' background-color: ' . $value . '; ';
                    }
                    break;
                case 'cell_border_top':
                    $check = explode(' ', $value);
                    if ($check[0] !== 'none' && $value !== '') {
                        $content = ' border-top: ' . $value . '; ';
                    }
                    break;
                case 'cell_border_right':
                    $check = explode(' ', $value);
                    if ($check[0] !== 'none' && $value !== '') {
                        $content = ' border-right: ' . $value . '; ';
                    }
                    break;
                case 'cell_border_bottom':
                    $check = explode(' ', $value);
                    if ($check[0] !== 'none' && $value !== '') {
                        $content = ' border-bottom: ' . $value . '; ';
                    }
                    break;
                case 'cell_border_left':
                    $check = explode(' ', $value);
                    if ($check[0] !== 'none' && $value !== '') {
                        $content = ' border-left: ' . $value . '; ';
                    }
                    break;
                case 'cell_font_family':
                    if ($value !== '') {
                        $content = ' font-family: ' . $value . '; ';
                    }
                    break;
                case 'cell_font_size':
                    if ($value !== '') {
                        $content = ' font-size: ' . $value . 'px;';
                    }
                    break;
                case 'cell_font_color':
                    if ($value !== '') {
                        $content = ' color: ' . $value . '; ';
                    }
                    break;
                case 'cell_font_bold':
                    if (!empty($value)) {
                        $content = ' font-weight: bold;';
                    }
                    break;
                case 'cell_font_italic':
                    if (!empty($value)) {
                        $content = ' font-style: italic;';
                    }
                    break;
                case 'cell_font_underline':
                    if (!empty($value)) {
                        $content = ' text-decoration: underline;';
                    }
                    break;
                case 'cell_text_align':
                    if ($value !== '') {
                        $content = ' text-align: ' . $value . '; ';
                    }
                    break;
                case 'cell_vertical_align':
                    if ($value !== '') {
                        $content = ' vertical-align: ' . $value . '; ';
                    }
                    break;
                case 'cell_padding_left':
                    if ($value !== '') {
                        $content = ' padding-left: ' . $value . 'px;';
                    }
                    break;
                case 'cell_padding_top':
                    if ($value !== '') {
                        $content = ' padding-top: ' . $value . 'px;';
                    }
                    break;
                case 'cell_padding_right':
                    if ($value !== '') {
                        $content = ' padding-right: ' . $value . 'px;';
                    }
                    break;
                case 'cell_padding_bottom':
                    if ($value !== '') {
                        $content = ' padding-bottom: ' . $value . 'px;';
                    }
                    break;
                case 'cell_background_radius_left_top':
                    if ($value !== '') {
                        $content = ' border-top-left-radius: ' . $value . 'px;';
                    }
                    break;
                case 'cell_background_radius_right_top':
                    if ($value !== '') {
                        $content = ' border-top-right-radius: ' . $value . 'px;';
                    }
                    break;
                case 'cell_background_radius_right_bottom':
                    if ($value !== '') {
                        $content = ' border-bottom-right-radius: ' . $value . 'px;';
                    }
                    break;
                case 'cell_background_radius_left_bottom':
                    if ($value !== '') {
                        $content = ' border-bottom-left-radius: ' . $value . 'px;';
                    }
                    break;
            }
        }

        return $content;
    }

    /**
     * Validate color
     *
     * @param string $color Color value
     *
     * @return boolean
     */
    private function validateColor($color)
    {
        $result = false;
        if (isset($color[0]) && $color[0] === '#') {
            $color = substr($color, 1);
        }

        //Check if color has 6 or 3 characters and get values
        if (strlen($color) === 6) {
            if (preg_match('/^[a-f0-9]{6}$/i', $color)) {
                //hex color is valid
                $result = true;
            }
        } elseif (strlen($color) === 3) {
            if (preg_match('/^[a-f0-9]{3}$/i', $color)) {
                //hex color is valid
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Build js for chart
     *
     * @param array  $data             Table content
     * @param string $type             Type chart
     * @param object $config           Chart option data
     * @param string $currency_symbol  Currency symbols
     * @param string $thousand_symbols Thousand symbols
     * @param string $decimal_symbols  Decimal Symbols
     *
     * @return false|string
     */
    private function buildJsChartData($data, $type, $config, $currency_symbol, $thousand_symbols, $decimal_symbols)
    {
        $result = '';

        if (!$config || !is_object($config)) {
            $config = new stdClass();
            $config->pieColors = '';
            $config->colors = '';
        }

        $config->dataUsing = isset($config->dataUsing) ? $config->dataUsing : 'row';
        $config->useFirstRowAsLabels = isset($config->useFirstRowAsLabels) ? $config->useFirstRowAsLabels : false;

        $dataSets = $this->getDataSets(
            $data,
            $config->dataUsing,
            $currency_symbol,
            $decimal_symbols,
            $thousand_symbols
        );

        if (!isset($dataSets->data) || (count($dataSets->data) === 0)) {
            return $result;
        }

        switch ($type) {
            case 'PolarArea':
            case 'Pie':
            case 'Doughnut':
//                $chartData = $this->convertForPie($dataSets->data, $dataSets->axisLabels, $config->pieColors);
                $chartData = $this->convertForLine($dataSets, $config, $config->pieColors, 'pieColors');
                break;

            case 'Bar':
            case 'Radar':
            case 'Line':
            default:
                $chartData = $this->convertForLine($dataSets, $config, $config->colors, 'colors');
                break;
        }

        $result = json_encode($chartData);
        return $result;
    }

    // check column is int

    /**
     * Replec cell value
     *
     * @param object $cellsData       Cell content
     * @param string $currency_symbol Currency symbols
     *
     * @return array
     */
    private function replaceCell($cellsData, $currency_symbol)
    {
        $data1 = array();
        $i = 0;
        $data2 = -1;
        foreach ($cellsData as $k => $v) {
            $v1 = preg_replace('/[0-9\.\,\-]/', '', $v);
            $v1 = str_replace($currency_symbol, '', $v1);
            if (trim($v1) === '') {
                $data1[$i] = $k;
                $i++;
            } else {
                $data2 = $k;
            }
        }
        $data = array();
        $data[0] = $data1;
        if ($data2 !== -1) {
            $data[1] = $data2;
        }
        return $data;
    }

    /**
     * Get data cell to chart
     *
     * @param array  $cellsData       Data cells
     * @param string $dataUsing       Data Switch
     * @param string $currency_symbol Currency symbol
     * @param string $decimal_symbol  Decimal symbol
     * @param string $thousand_symbol Thousand symbol
     *
     * @return stdClass
     */
    private function getDataSets($cellsData, $dataUsing, $currency_symbol, $decimal_symbol, $thousand_symbol)
    {
        $result = new stdClass();
        $result->data = array();
        $result->graphLabel = array();//text in line
        $result->axisLabels = array();//text in x-axis
        $result->currency_symbol = array();//text in x-axis
        $axisLabels = array();
        $deleteLine = array();
        $result->arrayShiftData = false;

        if ($dataUsing !== 'row') {//convert to column type
            $cellsData = $this->transposeArr($cellsData);
        }

        $result->deleteData = array();

        foreach ($cellsData as $k => $value) {
            $checkCellsHaveNaN = 0;
            $checkCellsHaveNanNull = 0;
            $countCellInLine = count($value);
            $cellsData1 = array();
            $deleteData1 = array();
            $check_currency_symbol = 0;

            $v = $value;
            for ($i = 0; $i < $countCellInLine; $i++) {
                $v[$i] = str_replace($thousand_symbol, '', $v[$i]);
                $v[$i] = str_replace($currency_symbol, '', $v[$i]);
                $v[$i] = str_replace($decimal_symbol, '.', $v[$i]);

                $cellsData1[$i] = preg_replace('/[^0-9\,\-]/', '', $v[$i]);

                $v[$i] = preg_replace('/[0-9\.\,\-| ]/', '', $v[$i]);

                if ($v[$i] !== '') {//have strange characters
                    $checkCellsHaveNaN++;
                }

                if ($v[$i] !== '' || $value[$i] === '') {//have strange characters or is null
                    $checkCellsHaveNanNull++;
                    $deleteData1[$i] = 1;
                }
                if ($cellsData1[$i] !== '' && $value[$i] !== '' && strrpos($value[$i], $currency_symbol) !== false) {
                    $check_currency_symbol++;
                }
            }

            $line_die = $checkCellsHaveNaN === $countCellInLine || ($countCellInLine > 2 && $checkCellsHaveNaN + 2 > $countCellInLine);

            if ($line_die || $k === 0) {//line Have NaN or first line
                $axisLabels[] = $value;
                $deleteLine[] = $k;
            }
            if (!$line_die && $checkCellsHaveNanNull < $countCellInLine) {//get this line, that have cell value
                foreach ($deleteData1 as $ii => $deleteData) {
                    if (!isset($result->deleteData[$ii])) {
                        $result->deleteData[$ii] = 0;
                    }
                    $result->deleteData[$ii] += 1;
                }
                $result->data[] = $cellsData1;//array key 1, 2, 3,...|| in $cellsData1, may contain non-validated cells
                $result->graphLabel[] = $value[0];//array key 1, 2, 3,...||$value[0] first value

                if ($check_currency_symbol > 1) {
                    $result->currency_symbol[] = 1;
                } else {
                    $result->currency_symbol[] = -1;
                }
            }
        }

        $numberLine = count($result->data);
        $useFirstRowAsGraph = isset($this->useFirstRowAsGraph) ? $this->useFirstRowAsGraph : true;
        //if line number > 1 then not get cell is graphLabel else < 1 then get it
        if ($numberLine > 0) {//have > 1 line in chart
            if (count($axisLabels) > 0) {//can remove
                $result->axisLabels = $axisLabels[count($axisLabels) - 1];
            }

            if ($useFirstRowAsGraph) {
                for ($i = 0; $i < $numberLine; $i++) {
                    array_shift($result->data[$i]);
                }
                $result->arrayShiftData = true;

                if (isset($result->deleteData[0])) {
                    unset($result->deleteData[0]);
                }
                array_shift($result->axisLabels);
            }
        }
//        if (count($axisLabels) > 0) {//useFirstRowAsGraph become useless
//            $result->axisLabels = $axisLabels[0];
//        } elseif ($numberLine > 0) {//axisLabels from $cellsData[0] || all line be passed validated
//            $result->axisLabels = $cellsData[0];
//            if ($useFirstRowAsGraph !== true) {
//                array_shift($result->data);
//                array_shift($result->currency_symbol);
//                array_shift($result->graphLabel);
//            }
//        }
//
//        if (!empty($result->arrayShiftData)) {
//            array_shift($result->axisLabels);
//        }
//
//        foreach ($result->deleteData as $ii => $deleteData) {//not deleted yet cells not pass
//            if ($numberLine !== $deleteData) {
//                unset($result->deleteData[$ii]);
//            }
//        }

        $result->data = $this->convertToNumber($result->data);

        return $result;
    }

    /**
     * Get valid  chart data
     *
     * @param array  $cellsData       Cells content
     * @param string $currency_symbol Currency symbols
     * @param string $decimal_symbol  Decimal symbol
     * @param string $thousand_symbol Thousand symbol
     *
     * @return array
     */
    public function getValidChartData($cellsData, $currency_symbol, $decimal_symbol, $thousand_symbol)
    {
        $results = array();
        $resultIndexes = array();
        $tempIndexes = array();
        $rowIndexes = array();
        $countFirstCellsData = count($cellsData[0]);
        $countCellsData = count($cellsData);
        for ($i = 0; $i < $countFirstCellsData; $i++) {
            $resultIndexes[$i] = $i;
        }
        for ($i = 0; $i < $countCellsData; $i++) {
            $ValidRow = $this->isGetValidRow($cellsData[$i], $currency_symbol, $decimal_symbol, $thousand_symbol);
            if ($ValidRow['validRow'] > 1) {
                array_push($results, $ValidRow['cellDataVal']);
                array_push($rowIndexes, $i);
                $tempIndexes = $ValidRow['getValidRow'];
                $resultIndexes = $this->intersection($tempIndexes, $resultIndexes, $ValidRow['validRow']);
            }
        }
        $count = count($results);
        for ($i = 0; $i < $count; $i++) {
            $tempArr = array();
            $countIndex = count($resultIndexes);
            for ($j = 0; $j < $countIndex; $j++) {
                array_push($tempArr, $results[$i][$resultIndexes[$j]]);
            }
            $results[$i] = $tempArr;
        }
        return array($results, $resultIndexes, $rowIndexes);
    }

    //check and get index of valid number in the array

    /**
     * Get valid row
     *
     * @param array  $cellData        Cell content to row
     * @param string $currency_symbol Currency symbols
     * @param string $decimal_symbol  Decimal symbol
     * @param string $thousand_symbol Thousand symbol
     *
     * @return array
     */
    public function isGetValidRow($cellData, $currency_symbol, $decimal_symbol, $thousand_symbol)
    {
        $countCellData = count($cellData);
        $count = 0;
        $result = array();
        for ($i = 0; $i < $countCellData; $i++) {
            $cellData[$i] = (string)$cellData[$i];
            $cellData[$i] = str_replace($currency_symbol, '', $cellData[$i]);
            $cellData[$i] = str_replace($thousand_symbol, '', $cellData[$i]);
            $cellData[$i] = str_replace($decimal_symbol, '.', $cellData[$i]);

            $cellData[$i] = str_replace('/[\,\-]/g', '', $cellData[$i]);
            $cellData[$i] = str_replace('/[ ]/g', '', $cellData[$i]);
            $v = preg_replace('/[0-9\\.]/', '', $cellData[$i]);
            if ($v === '') {
                $count++;
                array_push($result, $i);
            }
        }
        return array('validRow' => $count, 'getValidRow' => $result, 'cellDataVal' => $cellData);
    }

    /**
     * Get intersection values of two array
     *
     * @param array   $tempIndexes       Temp index
     * @param array   $resultIndexes     Result index
     * @param integer $lengthTempIndexes Count temp index
     *
     * @return array
     */
    public function intersection($tempIndexes, $resultIndexes, $lengthTempIndexes)
    {
        $data = array();
        for ($i = 0; $i < $lengthTempIndexes; $i++) {
            if (in_array($tempIndexes[$i], $resultIndexes)) {
                array_push($data, $tempIndexes[$i]);
            }
        }
        return $data;
    }

    /**
     * Get empty array
     *
     * @param integer $len Count value array
     *
     * @return array
     */
    public function getEmptyArray($len)
    {
        $result = array();
        for ($i = 0; $i < $len; $i++) {
            $result[$i] = '    ';
        }
        return $result;
    }

    /**
     * Convert string to integer
     *
     * @param array|string $arr Value to convert
     *
     * @return array
     */
    public function convertToNumber($arr)
    {
        if (is_array($arr)) {
            $countArr = count($arr);
            for ($i = 0; $i < $countArr; $i++) {
                if (is_array($arr[$i])) {
                    $countArrI = count($arr[$i]);
                    for ($j = 0; $j < $countArrI; $j++) {
                        $arr[$i][$j] = str_replace(',', '', $arr[$i][$j]);
                    }
                } else {
                    $arr[$i] = str_replace(',', '', $arr[$i]);
                }
            }
        } else {
            $arr = str_replace(',', '', $arr);
        }
        return $arr;
    }

    /**
     * Convert values of in-line cells
     *
     * @param object $dataSets       Data chart after change
     * @param object $config         Use First Row As Labels
     * @param string $colors         Color lines in chart
     * @param string $checkTyleColor Color input in chart
     *
     * @return stdClass
     */
    private function convertForLine($dataSets, $config, $colors, $checkTyleColor)
    {
        $result = new stdClass();
        $result->datasets = array();
        if (is_array($dataSets)) {
            $dataSets1 = $dataSets;
        } elseif (is_object($dataSets)) {
            $dataSets1 = $dataSets->data;
        }

        if (!is_array($dataSets1) || (count($dataSets1) === 0)) {
            return $result;
        }

        $useFirstRowAsLabels = $config->useFirstRowAsLabels;

        $numberLine = count($dataSets1);
        $countDatasets = count($dataSets1[0]);
        for ($i = 0; $i < $numberLine; $i++) {
            $dataSet = new stdClass();
            $dataSet->label = $dataSets->graphLabel[$i];
            $dataSet->currency_symbol = $dataSets->currency_symbol[$i];
            $result->labels = array();

            if ($checkTyleColor === 'pieColors') {
                $dataSet->highlight = array();
                $dataSet->backgroundColor = array();
                $dataSet->borderColor = array();
                $dataSet->pointBackgroundColor = array();
                $dataSet->pointColor = array();
                $dataSet->pointBorderColor = array();
                $dataSet->pointHighlightFill = array();
            }

            for ($j = 0; $j < $countDatasets; $j++) {
                if (!(isset($dataSets->deleteData)
                    && (($dataSets->arrayShiftData && isset($dataSets->deleteData[$j + 1]) && $dataSets->deleteData[$j + 1] === $numberLine
                    || (!$dataSets->arrayShiftData && isset($dataSets->deleteData[$j]) && $dataSets->deleteData[$j] === $numberLine)))
                )) {//has delete data
                    if (!isset($dataSet->data)) {
                        $dataSet->data = array();
                    }
                    $dataSet->data[] = $dataSets1[$i][$j];//data da duoc remove arrayShiftData tu truoc

                    if ($useFirstRowAsLabels) {
                        $result->labels[] = $dataSets->axisLabels[$j];
                    } else {
                        $result->labels[] = '';
                    }

                    if ($checkTyleColor === 'pieColors') {
                        $pieColors = $this->getStyleSet($j, $colors);
                        $dataSet->highlight[] = $pieColors->highlight;
                        $dataSet->backgroundColor[] = $pieColors->backgroundColor;
                        $dataSet->borderColor[] = $pieColors->borderColor;
                        $dataSet->pointBackgroundColor[] = $pieColors->pointBackgroundColor;
                        $dataSet->pointColor[] = $pieColors->pointColor;
                        $dataSet->pointBorderColor[] = $pieColors->pointBorderColor;
                        $dataSet->pointHighlightFill[] = $pieColors->pointHighlightFill;
                    }
                }
            }
            if ($checkTyleColor !== 'pieColors') {
                $styleSet = $this->getStyleSet($i, $colors);
                $dataSet = (object)array_merge((array)$dataSet, (array)$styleSet);
            }
            $result->datasets[$i] = $dataSet;
        }

        return $result;
    }

    /**
     * Convert color
     *
     * @param array  $datacell   Cell data
     * @param string $axisLabels Axis labels
     * @param string $pieColors  Pie colors
     *
     * @return array
     */
    private function convertForPie($datacell, $axisLabels, $pieColors)
    {
        require_once('chartStyleSet.php');

        $datas = array();
        $data = array();

        $defaultColors = array('#F7464A', '#46BFBD', '#FDB45C', '#949FB1', '#4D5360');
        $highlights = array('#FF5A5E', '#5AD3D1', '#FFC870', '#A8B3C5', '#616774');
        if (!$pieColors) {
            $colors = $defaultColors;
        } else {
            $colors = explode(',', $pieColors);
        }
        $datas['labels'] = $axisLabels;
        $countCell = count($datacell);
        for ($i = 0; $i < $countCell; $i++) {
            $data[$i]['data'] = $datacell[$i];
            $data[$i]['backgroundColor'] = array();
            $data[$i]['borderColor'] = array();
            $data[$i]['pointBackgroundColor'] = array();
            $data[$i]['pointBorderColor'] = array();
            $countCellI = count($datacell[$i]);
            for ($j = 0; $j < $countCellI; $j++) {
                if (!isset($colors[$j])) {
                    $colors[$j] = $defaultColors[$j % 5];
                }
                $color = new ChartStyleSet($colors[$j], $i * 0.1);
                $data[$i]['backgroundColor'][$j] = $color->backgroundColor;
                $data[$i]['borderColor'][$j] = $color->borderColor;
                $data[$i]['pointBackgroundColor'][$j] = $color->pointBackgroundColor;
                $data[$i]['pointBorderColor'][$j] = $color->pointBorderColor;
            }
        }
        $datas['datasets'] = $data;
        return $datas;
    }

    /**
     * Alter brightness
     *
     * @param string  $colourstr Color str
     * @param integer $steps     Steps
     *
     * @return string
     */
    public function alter_brightness($colourstr, $steps)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Function unknown location of use
    {
        $colourstr = str_replace('#', '', $colourstr);
        $rhex = substr($colourstr, 0, 2);
        $ghex = substr($colourstr, 2, 2);
        $bhex = substr($colourstr, 4, 2);

        $r = hexdec($rhex);
        $g = hexdec($ghex);
        $b = hexdec($bhex);

        $r = max(0, min(255, $r + $r * $steps));
        $g = max(0, min(255, $g + $g * $steps));
        $b = max(0, min(255, $b + $b * $steps));

        return '#' . dechex($r) . dechex($g) . dechex($b);
    }

    /**
     * Get color style
     *
     * @param integer $i      Number array
     * @param string  $colors Color
     *
     * @return object
     */
    private function getStyleSet($i, $colors)
    {
        $result = null;
        $defaultColors = array('#DCDCDC', '#97BBCD', '#4C839E');

        if (!$colors) {
            $arrColors = $defaultColors;
        } else {
            $arrColors = explode(',', $colors);
        }

        if (count($arrColors) && isset($arrColors[$i])) {
            $color = $arrColors[$i];
        } else {
            $color = $defaultColors[$i % 3];
        }

        require_once('chartStyleSet.php');
        $result = new ChartStyleSet($color);
        return $result;
    }

    /**
     * Check number array
     *
     * @param array  $arr             Array content
     * @param string $currency_symbol Currency symbols
     *
     * @return boolean
     */
    private function isNumbericArray($arr, $currency_symbol)
    {
        $valid = true;
        $count = count($arr);
        for ($c = 0; $c < $count; $c++) {
            if ($arr[$c] !== '') {
                $arr[$c] = str_replace($currency_symbol, '', (string)$arr[$c]);
                $arr[$c] = preg_replace('/[\.\,\-]/', '', $arr[$c]);
                if (!is_numeric($arr[$c])) {
                    $valid = false;
                }
            }
        }
        return $valid;
    }

    /**
     * Check row has number
     *
     * @param array  $cells           Cells array
     * @param string $currency_symbol Currency symbols
     *
     * @return boolean
     */
    private function hasNumbericRow($cells, $currency_symbol)
    {
        $rValid = true;
        $rNaN = 0;
        $countCells = count($cells);
        for ($r = 0; $r < $countCells; $r++) {
            $valid = true;
            $countCellR = count($cells[$r]);
            for ($c = 0; $c < $countCellR; $c++) {
                $cells[$r][$c] = str_replace($currency_symbol, '', (string)$cells[$r][$c]);
                if (!is_numeric(preg_replace('/[\.\,\-]/', '', $cells[$r][$c]))) {
                    $valid = false;
                }
            }

            if (!$valid) {
                $rNaN++;
            }
        }
        if ($rNaN === count($cells)) {
            $rValid = false;
        }

        return $rValid;
    }

    /**
     * Transpose array
     *
     * @param array $array Array test
     *
     * @return array
     */
    private function transposeArr($array)
    {
        $transposed_array = array();
        if (is_array($array)) {
            foreach ($array as $row_key => $row) {
                if (is_array($row) && !empty($row)) { //check to see if there is a second dimension
                    foreach ($row as $column_key => $element) {
                        $transposed_array[$column_key][$row_key] = $element;
                    }
                } else {
                    $transposed_array[0][$row_key] = $row;
                }
            }
            return $transposed_array;
        }
    }

    /**
     * Get chart data
     *
     * @param string $cellRange Cell range
     * @param string $tData     Table data
     *
     * @return array
     */
    private function getChartData($cellRange, $tData)
    {
        $result = array();
        $arr_cellRanges = json_decode($cellRange);
        $countCellRanges = count($arr_cellRanges);
        for ($i = 0; $i < $countCellRanges; $i++) {
            $row = $arr_cellRanges[$i];
            $countRow = count($row);
            for ($j = 0; $j < $countRow; $j++) {
                $result[$i][$j] = $this->getCellData($row[$j], $tData);
            }
        }

        return $result;
    }

    /**
     * Get cell data
     *
     * @param string $cellPos   Cell pos
     * @param array  $tableData Table data
     *
     * @return string
     */
    private function getCellData($cellPos, $tableData)
    {
        $result = '';
        list($r, $c) = explode(':', $cellPos);
        $result = $tableData[$r][$c];
        return $result;
    }
}
