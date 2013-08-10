<?php

/**
 * @property CApplication $owner
 */
class MaintainApplicationBehavior extends CBehavior
{
    public $downRoute = 'site/down';

    public $maintainFile;

    /**
     * @return array the behavior events.
     */
    public function events()
    {
        return array(
            'onBeginRequest' => 'checkDown',
        );
    }

    public function checkDown()
    {
        if (file_exists($this->maintainFile)) {
            $this->owner->catchAllRequest = array($this->downRoute);
        }
    }
} 