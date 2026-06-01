<?php
/**    
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;
// This file should not be used at all! Instead, please use Composer's autoload.
// It purely exists to reduce the maintenance burden for existing code using
// this repository.

// Check if people used Composer to include this project in theirs

    require __DIR__ . '/src/enums/AbstractEnum.php';
    require __DIR__ . '/src/enums/DatatypeEnum.php';
    require __DIR__ . '/src/enums/FileProcessingModeEnum.php';
    require __DIR__ . '/src/enums/SortEnum.php';
    require __DIR__ . '/src/extensions/DatatypeTrait.php';
    require __DIR__ . '/src/Csv.php';


// This wrapper class should not be used by new projects. Please look at the
// examples to find the up-to-date way of using this repo.
class parseCSV extends ParseCsv\Csv {

}
