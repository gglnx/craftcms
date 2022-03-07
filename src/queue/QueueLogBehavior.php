<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use Craft;
use craft\log\FileTarget;
use yii\queue\ExecEvent;

/**
 * Queue Log Behavior
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class QueueLogBehavior extends VerboseBehavior
{
    /**
     * @var float timestamp
     */
    private float $_jobStartedAt;

    /**
     * @var bool Whether any jobs have executed yet
     */
    private bool $_jobExecuted = false;

    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            Queue::EVENT_BEFORE_EXEC => 'beforeExec',
            Queue::EVENT_AFTER_EXEC => 'afterExec',
            Queue::EVENT_AFTER_ERROR => 'afterError',
        ];
    }

    /**
     * @param ExecEvent $event
     */
    public function beforeExec(ExecEvent $event): void
    {
        if (!$this->_jobExecuted) {
            $this->_changeLogFile();
        }

        $this->_jobStartedAt = microtime(true);
        Craft::info(sprintf('%s - Started', parent::jobTitle($event)), __METHOD__);
    }

    /**
     * @inheritdoc
     */
    public function afterExec(ExecEvent $event): void
    {
        $duration = $this->_formattedDuration();
        Craft::info(sprintf('%s - Done (time: %s)', parent::jobTitle($event), $duration), __METHOD__);
    }

    /**
     * @inheritdoc
     */
    public function afterError(ExecEvent $event): void
    {
        $message = sprintf('%s - Error', parent::jobTitle($event));

        if (isset($this->_jobStartedAt)) {
            $message .= sprintf(' (time: %s)', $this->_formattedDuration());
        }

        if ($event->error) {
            $message .= sprintf(': %s', $event->error->getMessage());
        }

        Craft::error($message, __METHOD__);

        if ($event->error) {
            Craft::$app->getErrorHandler()->logException($event->error);
        }
    }

    /**
     * Changes the file that logs will get flushed to.
     */
    private function _changeLogFile(): void
    {
        $logDispatcher = Craft::$app->getLog();

        foreach ($logDispatcher->targets as $target) {
            if ($target instanceof FileTarget) {
                // Log to queue.log
                $target->logFile = Craft::getAlias('@storage/logs/queue.log');

                // Don't log global vars
                $target->logVars = [];

                // Prevent verbose system logs
                if (!YII_DEBUG) {
                    $target->except = ['yii\*'];
                    $target->setLevels(['info', 'warning', 'error']);
                }

                break;
            }
        }
    }

    /**
     * Returns the job execution time in seconds.
     *
     * @return string
     */
    private function _formattedDuration(): string
    {
        return sprintf('%.3f', microtime(true) - $this->_jobStartedAt) . 's';
    }
}
