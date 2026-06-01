<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die;

//print_r($this->toc_tree);
$toc_tree	=	$this->toc_tree;
$scorm_data	=	$this->scorm_data;

?>
<script>


$(function() {
    jQuery('#tjlms_toctree').on('click', '.toggle', function () {
        var el = jQuery(this);
        var li = el.closest('li');
         if (jQuery(li).has('ul').length)
		{
			if (li.hasClass('tjcollapse')) {
				li.removeClass('tjcollapse').addClass('tjexpand');
				jQuery(li).find("ul").hide();

			} else {
				li.removeClass('tjexpand').addClass('tjcollapse');
				jQuery(li).find("ul").show();
			}
		}

    });
});
</script>

<div class="span1 tjlms_scorm_toc" id="tjlms_scorm_toc" style="width:auto;">
	<div class="tjlms_toc_panel" style="height:99%">
		<div class="toc-panel-header">
			<div class="toc-tool  toc-tool-collapse" id="ext-gen9">&nbsp;</div>
		</div>
		<div id="tjlms_scorm_toc_tool"  class="tjlms_scorm_toc_tool">
				<div class="tjlms_scorm_toc_title"  id="tjlms_scorm_toc_title">
					<h4><?php echo $toc_tree[0]->title;?></h4>
					<div title="Hide" class="collapse"></div>
				</div>
				<div class="tjlms-toc-toolbar" id="tjlms-toc-toolbar">
					<button id="" type="button" class="btn tjlms-toc-toolbar_btn tjlms-toc-toolbar_back">&nbsp;</button>
					<button id="" type="button" class="btn tjlms-toc-toolbar_btn tjlms-toc-toolbar_next">&nbsp;</button>
				</div>
		</div>
			<div class="tjlms_toc_tree_parent">
					<ul class='tjlms_toctree' id="tjlms_toctree">
					<?php
					foreach($toc_tree[0]->children as $parent_item)
					{
						?>
							<li data-depth="0" class="tjcollapse">
								<div class='tjlms_node_header'>
									<span class="toggle tjcollapse"></span>
									<div class="toc-tree-node-icon"></div>
										<?php if($parent_item->launch !='') { ?>
											<a class="sco-obj" style="cursor:pointer;" title="<?php echo $parent_item->url;?>"><?php echo $parent_item->title; ?></a>
										<?php }
											else{
												echo $parent_item->title;
											}?>
								</div>
								<?php
									if(isset($parent_item->children))
									{
										?>
										<ul data-depth="1">
										<?php
											foreach($parent_item->children as $item)
											{?>
											<li>
												<img border="0" src="components/com_tjlms/assets/images/application_go.png" alt="">
												<?php if($item->launch !='') { ?>
													<a class="sco-obj" style="cursor:pointer;" title="<?php echo $item->url;?>"><?php echo $item->title; ?></a>
												<?php }
													else{
														echo $item->title;
													}?>
											</li>
										<?php
											}
											?>
										</ul>
										<?php
									}
								?>
							</li>
						<?php
					}
					?>
					</ul>
				</div>
		</div>
		<div class="tjlms_tocpanel-collapsed">
			<div class="toc-tool toc-tool-expand">&nbsp;</div>
		</div>
	</div>


<?php
/*

    $result = new stdClass();
    $result->prerequisites = true;
    $result->incomplete = true;

    if (!$children) {
        $attemptsmade = scorm_get_attempt_count($user->id, $scorm);
        $result->attemptleft = $scorm->maxattempt == 0 ? 1 : $scorm->maxattempt - $attemptsmade;
    }

    if (!$children) {
        $result->toc = "<ul>\n";

        if (!$play && !empty($organizationsco))  {
            $result->toc .= "\t<li>".$organizationsco->title."</li>\n";
        }
    }

    $prevsco = '';
    if (!empty($scoes)) {
        foreach ($scoes as $sco) {
            $result->toc .= "\t<li>\n";
            $scoid = $sco->id;

            $sco->isvisible = true;

            if ($sco->isvisible) {
                $score = '';

                if (isset($usertracks[$sco->identifier])) {
                    $viewscore = has_capability('mod/scorm:viewscores', context_module::instance($cmid));
                    if (isset($usertracks[$sco->identifier]->score_raw) && $viewscore) {
                        if ($usertracks[$sco->identifier]->score_raw != '') {
                            $score = '('.get_string('score','scorm').':&nbsp;'.$usertracks[$sco->identifier]->score_raw.')';
                        }
                    }
                }

                if (!empty($sco->prereq)) {
                    if ($sco->id == $scoid) {
                        $result->prerequisites = true;
                    }

                    if (!empty($prevsco) && scorm_version_check($scorm->version, SCORM_13) && !empty($prevsco->hidecontinue)) {
                        if ($sco->scormtype == 'sco') {
                            $result->toc .= '<span>'.$sco->statusicon.'&nbsp;'.format_string($sco->title).'</span>';
                        } else {
                            $result->toc .= '<span>&nbsp;'.format_string($sco->title).'</span>';
                        }
                    } else if ($toclink == TOCFULLURL) {
                        $url = $CFG->wwwroot.'/mod/scorm/player.php?'.$sco->url;
                        if (!empty($sco->launch)) {
                            if ($sco->scormtype == 'sco') {
                                $result->toc .= $sco->statusicon.'&nbsp;<a href="'.$url.'">'.format_string($sco->title).'</a>'.$score."\n";
                            } else {
                                $result->toc .= '&nbsp;<a href="'.$url.'">'.format_string($sco->title).'</a>'.$score."\n";
                            }
                        } else {
                            if ($sco->scormtype == 'sco') {
                                $result->toc .= $sco->statusicon.'&nbsp;'.format_string($sco->title).$score."\n";
                            } else {
                                $result->toc .= '&nbsp;'.format_string($sco->title).$score."\n";
                            }
                        }
                    } else {
                        if (!empty($sco->launch)) {
                            if ($sco->scormtype == 'sco') {
                                $result->toc .= '<a title="'.$sco->url.'">'.$sco->statusicon.'&nbsp;'.format_string($sco->title).'&nbsp;'.$score.'</a>';
                            } else {
                                $result->toc .= '<a title="'.$sco->url.'">&nbsp;'.format_string($sco->title).'&nbsp;'.$score.'</a>';
                            }
                        } else {
                            if ($sco->scormtype == 'sco') {
                                $result->toc .= '<span>'.$sco->statusicon.'&nbsp;'.format_string($sco->title).'</span>';
                            } else {
                                $result->toc .= '<span>&nbsp;'.format_string($sco->title).'</span>';
                            }
                        }
                    }

                } else {
                    if ($play) {
                        if ($sco->scormtype == 'sco') {
                            $result->toc .= '<span>'.$sco->statusicon.'&nbsp;'.format_string($sco->title).'</span>';
                        } else {
                            $result->toc .= '&nbsp;'.format_string($sco->title).'</span>';
                        }
                    } else {
                        if ($sco->scormtype == 'sco') {
                            $result->toc .= $sco->statusicon.'&nbsp;'.format_string($sco->title)."\n";
                        } else {
                            $result->toc .= '&nbsp;'.format_string($sco->title)."\n";
                        }
                    }
                }

            } else {
                $result->toc .= "\t\t&nbsp;".format_string($sco->title)."\n";
            }

            if (!empty($sco->children)) {
                $result->toc .= "\n\t\t<ul>\n";
                $childresult = scorm_format_toc_for_treeview($user, $scorm, $sco->children, $usertracks, $cmid, $toclink, $currentorg, $attempt, $play, $organizationsco, true);
                $result->toc .= $childresult->toc;
                $result->toc .= "\t\t</ul>\n";
                $result->toc .= "\t</li>\n";
            } else {
                $result->toc .= "\t</li>\n";
            }
            $prevsco = $sco;
        }
        $result->incomplete = $sco->incomplete;
    }

    if (!$children) {
        $result->toc .= "</ul>\n";
    }
print_r($result);
    return $result;
}*/
