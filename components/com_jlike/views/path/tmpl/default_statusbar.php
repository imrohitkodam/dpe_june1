<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_JLike
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */

// No direct access
defined('_JEXEC') or die;
?>
<div class="container">
	<div class="row">
		<?php
		if (!empty($this->item->info))
		{
			?>
			<section>
				<div class="wizard">
					<div class="wizard-inner">
						<div class="connecting-line"></div>
						<ul class="nav nav-tabs" role="tablist">
							<?php
							$step = 1;

							foreach ($this->item->info as $info)
							{
								?>
								<li>
									<a href="#step1" data-toggle="tab" aria-controls="step1" role="tab" title="Step <?php echo $step;?>">
										<span class="round-tab">
											<?php echo htmlspecialchars($info['title']);?>
										</span>
									</a>
								</li>
								<?php
								$step ++;
							}
							?>
						</ul>
					</div>
				</div>
			</section>
			<?php
		}
		?>
	</div>
</div>
