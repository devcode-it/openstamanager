<?php

// @codingStandardsIgnoreFile

namespace Helper;

// Select2 version 4.0 or greater helpers for the jQuery based replacement for select boxes.
// See: http://select2.github.io/select2
// Author: Tortue Torche <tortuetorche@spam.me>
// Author: Florian Krämer
// Author: Tom Walsh
// License: MIT
//
// Installation:
// * Put this file in your 'tests/_support/Helper' directory
// * Add it in your 'tests/acceptance.suite.yml' file, like this:
//    class_name: AcceptanceTester
//    modules:
//        enabled:
//            - WebDriver:
//              url: 'http://localhost:8000'
//              # ...
//            - \Helper\Select2
//
// * Then run ./vendor/bin/codecept build

class Select2 extends \Codeception\Module
{
    /**
     * Wait until the select2 component is loaded.
     *
     * @param $selector
     * @param int $timeout seconds. Default to 5
     */
    public function waitForSelect2($selector, $timeout = 5)
    {
        $t = $this->getAcceptanceModule();
        $selector = $this->getSelect2Selector($selector);
        $t->waitForJS('return !!jQuery("'.$selector.'").data("select2");', $timeout);
    }

    /**
     * Checks that the given option is not selected.
     *
     * @param $selector
     * @param $optionText
     * @param int $timeout seconds. Default to 5
     */
    public function dontSeeOptionIsSelectedForSelect2($selector, $optionText, $timeout = 5)
    {
        $t = $this->getAcceptanceModule();
        $selector = $this->getSelect2Selector($selector);
        $this->waitForSelect2($selector, $timeout);
        $script = $this->_optionIsSelectedForSelect2($selector, $optionText, false);
        $t->waitForJS($script, $timeout);
    }

    /**
     * Checks that the given option is selected.
     *
     * @param $selector
     * @param $optionText
     * @param int $timeout seconds. Default to 5
     */
    public function seeOptionIsSelectedForSelect2($selector, $optionText, $timeout = 5)
    {
        $t = $this->getAcceptanceModule();
        $selector = $this->getSelect2Selector($selector);
        $this->waitForSelect2($selector, $timeout);
        $script = $this->_optionIsSelectedForSelect2($selector, $optionText);
        $t->waitForJS($script, $timeout);
    }

    /**
     * Selects an option in a select2 component.
     *
     *   $t->selectOptionForSelect2('#my_select2', 'Option value');
     *   $t->selectOptionForSelect2('#my_select2', ['Option value 1', 'Option value 2']);
     *   $t->selectOptionForSelect2('#my_select2', ['text' => 'Option text']);
     *   $t->selectOptionForSelect2('#my_select2', ['id' => 'Option value', 'text' => 'Option text']);
     *
     * @param $selector
     * @param $option
     * @param int $timeout seconds. Default to 1
     */
    public function selectOptionForSelect2($selector, $option, $timeout = 5)
    {
        $t = $this->getAcceptanceModule();
        $selector = $this->getSelect2Selector($selector);
        $this->waitForSelect2($selector, $timeout);

        if (is_int($option)) {
            $option = (string) $option;
        }

        if (is_string($option) || (is_array($option) && array_values($option) === $option)) {
            $t->executeJS('jQuery("'.$selector.'").select2("val", '.json_encode($option).');', [$timeout]);
            $t->executeJS('jQuery("'.$selector.'").trigger("select2:select").trigger("change");', [$timeout]);
        } elseif (is_array($option)) {
            $optionId = 'null';
            if (isset($option['text']) && empty($option['id'])) {
                $optionText = $option['text'];
                $optionId = <<<EOT
function() {
  if (!\$.expr[':'].textEquals) {
    // Source: http://stackoverflow.com/a/26431267
    \$.expr[':'].textEquals = function(el, i, m) {
      var searchText = m[3];
      return $(el).text().trim() === searchText;
    }
  }
  // Find select option by text
  return \$("$selector").find("option:textEquals('$optionText'):first").val();
}();
EOT;
            }
            $jsonOption = json_encode($option);
            $script = <<<EOT
(function (\$) {
  var option = $jsonOption;
  if (!option.id) {
    option.id = $optionId;
  }
  \$("$selector").val(option.id).trigger('select2:select').trigger('change');
}(jQuery));
EOT;
            $t->executeJS($script, [$timeout]);
        } else {
            $t->fail();
        }
    }

    /**
     * Unselect an option in the given select2 component.
     *
     * @param $selector
     * @param $option
     * @param int $timeout seconds. Default to 1
     */
    public function unselectOptionForSelect2($selector, $option = null, $timeout = 1)
    {
        $t = $this->getAcceptanceModule();
        $selector = $this->getSelect2Selector($selector);
        $this->waitForSelect2($selector, $timeout);
        if ($option && is_string($option)) {
            $script = <<<EOT
(function (\$) {
  var values = \$("$selector").select2("val");
  var index = values.indexOf("$option");
  if (index > -1) {
    values.splice(index, 1);
  }
  \$("$selector").select2("val", values);
  \$(\$("$selector").trigger("select2:select").trigger("change");
}(jQuery));
EOT;
            $t->executeJS($script, [$timeout]);
        } else {
            $t->executeJS('jQuery("'.$selector.'").select2("val", "");', [$timeout]);
            $t->executeJS('jQuery("'.$selector.'").trigger("select2:select").trigger("change");', [$timeout]);
        }
    }

    /**
     * Open the Select2 component.
     *
     * @param string $selector
     */
    public function openSelect2($selector)
    {
        $t = $this->getAcceptanceModule();
        $selector = $this->getSelect2Selector($selector);
        $this->waitForSelect2($selector);
        $t->executeJS('jQuery("'.$selector.'").select2("open");');
    }

    /**
     * Close the Select2 component.
     *
     * @param string $selector
     */
    public function closeSelect2($selector)
    {
        $t = $this->getAcceptanceModule();
        $selector = $this->getSelect2Selector($selector);
        $this->waitForSelect2($selector);
        $t->executeJS('jQuery("'.$selector.'").select2("close");');
    }

    /**
     * @param $selector
     * @param $optionText
     * @param bool $expectedReturn Default to true
     *
     * @return string JavaScript
     */
    protected function _optionIsSelectedForSelect2($selector, $optionText, $expectedReturn = true)
    {
        $returnFlag = $expectedReturn === true ? '' : '!';

        return $script = <<<EOT
return (function (\$) {
  var isSelected = false;
  var values = \$("$selector").val();
  values = \$.isArray(values ) ? values : [values];
  if (values && values.length > 0) {
    isSelected = values.some(function (data) {
      if (data && data.text && data.text === "$optionText") {
        return data;
      }
    });
  }
  return ${returnFlag}isSelected;
}(jQuery));
EOT;
    }

    protected function getSelect2Selector($selector)
    {
        return $selector;
        //return preg_replace("/^\#((?!s2id_).+)$/", '#s2id_$1', $selector);
    }

    protected function getAcceptanceModule()
    {
        if (!$this->hasModule('WebDriver')) {
            throw new \Exception('You must enable the WebDriver module', 1);
        }

        return $this->getModule('WebDriver');
    }
}
