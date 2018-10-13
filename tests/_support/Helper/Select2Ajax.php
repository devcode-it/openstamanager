<?php

// @codingStandardsIgnoreFile

namespace Helper;

class Select2Ajax extends Select2
{
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
            $t->executeJS('jQuery("'.$selector.'").selectSetNew('.json_encode($option).', "ID: " + '.json_encode($option).');', [$timeout]);
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
}
