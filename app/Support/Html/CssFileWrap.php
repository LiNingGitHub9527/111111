<?php

namespace App\Support\Html;

use Sabberworm\CSS\CSSList\AtRuleBlockList;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\RuleSet\DeclarationBlock;

class CssFileWrap
{
    public static function prepend($mark, $cssContent, $pretty = true)
    {
        $oParser = new Parser($cssContent);
        $oCss = $oParser->parse();
        foreach ($oCss->getAllDeclarationBlocks() as $oBlock) {
            foreach ($oBlock->getSelectors() as $oSelector) {
                //Loop over all selector parts (the comma-separated strings in a selector) and prepend the id
                $oSelector->setSelector($mark . ' ' . $oSelector->getSelector());
            }
        }

        if ($pretty) {
            $output = $oCss->render(OutputFormat::createPretty());
        } else {
            $output = $oCss->render();
        }

        return $output;
    }

    public static function prepend4Media($mark, $cssContent, $pretty = true)
    {
        $oParser = new Parser($cssContent);
        $oCss = $oParser->parse();

        foreach ($oCss->getContents() as $content) {
            if ($content instanceof AtRuleBlockList) {
                if ($content->atRuleName() == 'media') {
                    $rule = CssFileWrap::str2Array($content->atRuleArgs());
                    if (!empty($rule['min-width'])) {
                        if (strpos($rule['min-width'], 'px') !== false) {
                            if (intval($rule['min-width']) >= 750) {
                                foreach ($content->getContents() as $conBlock) {
                                    if ($conBlock instanceof DeclarationBlock) {
                                        foreach ($conBlock->getSelectors() as $oSelector) {
                                            $oSelector->setSelector($mark . ' ' . $oSelector->getSelector());
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($pretty) {
            $output = $oCss->render(OutputFormat::createPretty());
        } else {
            $output = $oCss->render();
        }

        return $output;
    }

    public static function str2Array($str)
    {
        $str = str_replace('(', '', $str);
        $str = str_replace(')', '', $str);
        $str = str_replace(' ', '', $str);
        $array = explode(':', $str);
        return [$array[0] => $array[1]];
    }

}
