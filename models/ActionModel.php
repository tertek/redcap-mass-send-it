<?php

namespace STPH\massSendIt;
use ReflectionClass;
use ReflectionProperty;


class ActionModel {

    protected function getPublicProperties() {
        $reflection = new ReflectionClass($this);
        $vars = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $publicProperties = [];

        foreach ($vars as $publicVar) {
            $publicProperties[] = $publicVar->getName();
        }

        return $publicProperties;
    }

    public function getFields() {
        return implode(",", $this->getPublicProperties());
    }

}