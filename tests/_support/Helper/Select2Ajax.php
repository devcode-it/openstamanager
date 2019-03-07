<?php // @codingStandardsIgnoreFile

namespace Helper;

/**
 * Select2 version 4.0 or greater helpers for the jQuery based replacement for select boxes (Ajax version).
 *
 * Installation:
 * - Put this file in your 'tests/_support/Helper' directory
 * - Add it in your 'tests/acceptance.suite.yml' file, like this:
 *      class_name: AcceptanceTester
 *      modules:
 *          enabled:
 *              - WebDriver:
 *              # ...
 *              - \Helper\Select2Ajax
 * - Run ./vendor/bin/codecept build
 *
 * @see http://select2.github.io/select2
 * @author Thomas Zilio
 *
 * @license MIT
 *
 */
class Select2Ajax extends Select2
{
    /**
     * Selects an option in a select2 component.
     *
     * @param $selector
     * @param $option
     * @param int $timeout seconds. Default to 1
     */
    public function selectByTextOrId($selector, $option, $timeout = 5)
    {
        $code = '
    $(options).each(function () {
        if($(this).text == "'.$option.'" || $(this).id == "'.$option.'") {
            $("'.$selector.'").selectSetNew(this.id, this.text);
        }
    });';

        $this->execute($selector, $timeout, $code);
    }

    public function selectByPosition($selector, $position, $timeout = 5)
    {
        $code = '
    var result = options['.$position.'];
    $("'.$selector.'").selectSetNew(result.id, result.text);';

        $this->execute($selector, $timeout, $code);
    }

    protected function execute($selector, $timeout, $code)
    {
        $t = $this->getAcceptanceModule();
        $selector = $this->getSelect2Selector($selector);
        $this->waitForSelect2($selector, $timeout);

        if (is_int($option)) {
            $option = (string) $option;
        }

        $results_selector = str_replace('#', '', $selector);

        $script = <<<EOT
$(document).ready(function() {
    var children = $("#select2-$results_selector-results").children();

    var options = [];
    children.each(function () {
        var data = $(this)[0];
        var output = Object.entries(data).map(([key, value]) => ({key,value}));

        if(output[0]) {
            options.push(output[0].value.data);
        }
    })

    $code
});
EOT;

        $t->executeJS($script, [$timeout]);
    }
}
