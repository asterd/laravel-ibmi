<?php

namespace Cooperl\IBMi\Program;

use Cooperl\IBMi\Facades\ToolkitService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Executes IBMi Commands
 *
 * @author Cassiano Vailati <cassvail>
 */
class Command implements LoggerAwareInterface
{
    use XMLToolkitAwareTrait, LoggerAwareTrait;

    /**
     * @var string
     */
    protected $command = '';

    /**
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger=null)
    {
        $this->setLogger($logger);
    }

    /**
     * @throws \Exception
     */
    public function executeCommand($command, $output = false, $interactive = false)
    {
        $logger = $this->getLogger();

        try
        {
            $this->debug("Execute Command $command");

            //CLCommand($Command)
            if ($output) {
                $result = ToolkitService::CLCommandWithOutput($command);
            }
            else if($interactive)
            {
                $result = ToolkitService::CLInteractiveCommand($command);
            }
            else
            {
                $result = ToolkitService::CLCommand($command);
            }

            if(!$result)
            {
                if ($logger) {
                    $logger->error(ToolkitService::getErrorMsg());
                    $logger->error(ToolkitService::getErrorCode());
                    $logger->error(ToolkitService::getErrorDataStructXml());
                }

                throw new \RuntimeException(sprintf('%s - %s - %s',
                        ToolkitService::getErrorMsg(),
                        ToolkitService::getErrorCode(),
                        ToolkitService::getErrorDataStructXml())
                );
            }

            //if($logger) $logger->debug($result);
        }
        catch (\Exception $e){
            $logger?->error($e->getMessage());
            throw $e;
        }

        return $result;
    }

    /**
     * @throws \Exception
     */
    public function exec()
    {
        return $this->executeCommand($this->command);
    }

    public function debug($message): void
    {
        $logger = $this->getLogger();
        $logger?->debug($message);
    }

    public function prettyExecute()
    {
       //TODO Implement method
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
