<?php

/**
 * Genera il prodotto cartesiano tra gli elementi dell'array di input.
 *
 * @param $input
 *
 * @return array|array[]
 */
if (!function_exists('cartesian')) {
    function cartesian($input)
    {
        $result = [[]];

        foreach ($input as $key => $values) {
            $append = [];

            foreach ($result as $product) {
                foreach ($values as $item) {
                    $product[$key] = $item;
                    $append[] = $product;
                }
            }

            $result = $append;
        }

        return $result;
    }
}