<?php
/**
 * Latest SEO Glossary module Default layout
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// no direct access
defined( '_JEXEC' ) or die ;
		$d = JFactory::getDocument();
		$d->addStyleSheet(JURI::base().'components/com_seoglossary/assets/css/style.css');
        $value = JFactory::getApplication()->input->getString( 'filter_search' ,strtolower( JText::_( 'COM_SEOGLOSSARY_SEARCH' ) ) . '...');
        $filter_options = JFactory::getApplication()->input->getString( 'glossarysearchmethod', 1 );

        $str1 = "";
        $str2 = "";
        $str3 = "";
        $str4 = "";
        if ( $filter_options == 1 )
        {
            $str1 = 'checked="checked" ';
        }
        else
            if ( $filter_options == 2 )
            {
                $str2 = 'checked="checked" ';
            }
            else
                if ( $filter_options == 3 )
                {
                    $str3 = 'checked="checked" ';
                }
                else
                    if ( $filter_options == 4 )
                    {
                        $str4 = 'checked="checked" ';
                    }
        $Itemid = JFactory::getApplication()->input->getInt( "Itemid", '' );
        $catid = JFactory::getApplication()->input->getInt( "catid", '' );
		$default = $params->get('default_search_mode', 1);
			$Itemid = SeoglossaryHelper::getGlossaryMenu($params->get('catid'));
			$item_id="";
				if($Itemid)
				{
					$item_id='&Itemid='.$Itemid;
					
				}
		$url = JRoute::_( 'index.php?option=com_seoglossary&view=glossaries&catid=' . $params->get('catid').$item_id);
?>

<div class="seoglossary_module_<?php echo htmlspecialchars( $params->get( 'moduleclass_sfx' ) );?>">
<?php
			$str = ' <div id="glossarysearchmodule"><form id="searchFormModule" name="searchForm" method="post" action="' . $url . '">
			<div id="glossarysearchmoduleheading">' . JText::_( "COM_SEOGLOSSARY_SEARCH_TEXT" ) . '</div>
   				<div class="input-append"><div class="srch-btn-inpt">
          <div class="srch-inpt"><input type="text" title="'.strtolower( JText::_( 'COM_SEOGLOSSARY_SEARCH' ) ).'" id="filter_search_module" name="filter_search"  value="' . $value . '" size="25"
   					onblur="if(this.value==&quot;&quot;) this.value=&quot;' . strtolower( JText::_( 'COM_SEOGLOSSARY_SEARCH' ) ) . '...&quot;" onfocus="if(this.value==&quot;' . strtolower( JText::_( 'COM_SEOGLOSSARY_SEARCH' ) ) . '...&quot;) this.value=&quot;&quot;"
   				/></div><div class="srch-btn">
                 <button type="submit" class="button btn btn-primary">' . JText::_( "COM_SEOGLOSSARY_SEARCH" ) . '</button>
				</div></div>
                <button onclick="document.getElementById(&quot;filter_search_module&quot;).value=&quot;&quot;;this.form.submit();" type="submit" class="button btn clear-btn">X</button>
                </div>';

        if ( $params->get( 'search_options', 1 ) != 0 )	{
            
            $str .=	'<div id="glossarysearchmethod">
            	<select name="glossarysearchmethod" class="form-select">
						<option ' . $str1 . ' value="1" '. ( ($default == 1) ? ' selected' : '' ) .' />' . JText::_( "COM_SEOGLOSSARY_SEARCH_BEGIN" ) . '
						<option ' . $str2 . ' value="2" '. ( ($default == 2) ? ' selected' : '' ) .' />' . JText::_( "COM_SEOGLOSSARY_SEARCH_CONTAINS" ) . '
						<option ' . $str3 . ' value="3" '. ( ($default == 3) ? ' selected' : '' ) .' />' . JText::_( "COM_SEOGLOSSARY_SEARCH_EXACT" );
            if ( $params->get( 'sound_like', 1 ) != 0 )
            {
                $str .= '<option ' . $str4 . ' value="4" '. ( ($default == 4) ? ' selected' : '' ) .' />' . JText::_( "COM_SEOGLOSSARY_SEARCH_LIKE" );
            }
            $str .= '</select></div>';
        } //endif ( $params->get( 'search_options', 1 ) != 0 )
        else {
            $str .= '<input type="hidden" name="glossarysearchmethod" value="'.$params->get('default_search_mode', 1).'" />';
        } // endelse ( $params->get( 'search_options', 1 ) != 0 )

        $str .= '</form>
		</div>';
		echo $str;
        ?>
</div>
