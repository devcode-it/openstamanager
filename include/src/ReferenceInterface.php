<?php

namespace Common;

interface ReferenceInterface
{
    public function getReferenceName();

    public function getReferenceNumber();

    public function getReferenceDate();

    public function getReference($text = null);
}
