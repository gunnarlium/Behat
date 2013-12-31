<?php

/*
 * This file is part of the Behat Testwork.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Testwork\Cli\Printer;

use Behat\Testwork\Printer\Exception\BadOutputPathException;
use Behat\Testwork\Printer\OutputPrinter;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Testwork console printer.
 *
 * Symfony Console based printer.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class CliOutputPrinter implements OutputPrinter
{
    /**
     * @var null|string
     */
    private $outputPath;
    /**
     * @var array
     */
    private $outputStyles = array();
    /**
     * @var null|Boolean
     */
    private $outputDecorated = null;
    /**
     * @var Boolean
     */
    private $verbose = false;
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Sets output path.
     *
     * @param string $path
     */
    public function setOutputPath($path)
    {
        $this->outputPath = $path;
        $this->flush();
    }

    /**
     * Returns output path.
     *
     * @return null|string
     */
    public function getOutputPath()
    {
        return $this->outputPath;
    }

    /**
     * Sets output styles.
     *
     * @param array $styles
     */
    public function setOutputStyles(array $styles)
    {
        $this->outputStyles = $styles;
        $this->flush();
    }

    /**
     * Returns output styles.
     *
     * @return array
     */
    public function getOutputStyles()
    {
        return $this->outputStyles;
    }

    /**
     * Forces output to be decorated.
     *
     * @param Boolean $decorated
     */
    public function setOutputDecorated($decorated)
    {
        $this->outputDecorated = $decorated;
        $this->flush();
    }

    /**
     * Returns output decoration status.
     *
     * @return null|Boolean
     */
    public function isOutputDecorated()
    {
        return $this->outputDecorated;
    }

    /**
     * Sets output to be verbose.
     *
     * @param Boolean $verbose
     */
    public function setVerbose($verbose = true)
    {
        $this->verbose = $verbose;
        $this->flush();
    }

    /**
     * Checks if output is verbose.
     *
     * @return Boolean
     */
    public function isVerbose()
    {
        return $this->verbose;
    }

    /**
     * Writes message(s) to output console.
     *
     * @param string|array $messages message or array of messages
     */
    final public function write($messages)
    {
        $this->getWritingConsole()->write($messages, false);
    }

    /**
     * Writes newlined message(s) to output console.
     *
     * @param string|array $messages message or array of messages
     */
    final public function writeln($messages = '')
    {
        $this->getWritingConsole()->write($messages, true);
    }

    /**
     * Clear output console, so on next write formatter will need to init (create) it again.
     */
    final public function flush()
    {
        $this->output = null;
    }

    /**
     * Creates output formatter that is used to create a console.
     *
     * @return OutputFormatter
     */
    protected function createOutputFormatter()
    {
        return new OutputFormatter();
    }

    /**
     * Configure output console parameters.
     *
     * @param StreamOutput $console
     */
    protected function configureOutputConsole(StreamOutput $console)
    {
        $verbosity = $this->verbose ? StreamOutput::VERBOSITY_VERBOSE : StreamOutput::VERBOSITY_NORMAL;
        $console->setVerbosity($verbosity);

        if (null !== $this->outputDecorated) {
            $console->getFormatter()->setDecorated($this->outputDecorated);
        }
    }

    /**
     * Returns new output stream for console.
     *
     * Override this method & call flushOutputConsole() to write output in another stream
     *
     * @return resource
     *
     * @throws BadOutputPathException
     */
    final protected function createOutputStream()
    {
        if (null === $this->outputPath) {
            $stream = fopen('php://stdout', 'w');
        } elseif (!is_dir($this->outputPath)) {
            $stream = fopen($this->outputPath, 'w');
        } else {
            throw new BadOutputPathException(sprintf(
                'Filename expected as `output_path` parameter, but got `%s`.',
                basename(str_replace('\\', '/', get_class($this))),
                $this->outputPath
            ), $this->outputPath);
        }

        return $stream;
    }

    /**
     * Returns new output console.
     *
     * @param null|resource $stream
     *
     * @return StreamOutput
     *
     * @uses createOutputStream()
     */
    final protected function createOutputConsole($stream = null)
    {
        $stream = $stream ? : $this->createOutputStream();
        $format = $this->createOutputFormatter();

        // set user-defined styles
        foreach ($this->outputStyles as $name => $options) {
            $style = new OutputFormatterStyle();

            if (isset($options[0])) {
                $style->setForeground($options[0]);
            }
            if (isset($options[1])) {
                $style->setBackground($options[1]);
            }
            if (isset($options[2])) {
                $style->setOptions($options[2]);
            }

            $format->setStyle($name, $style);
        }

        $console = new StreamOutput(
            $stream,
            StreamOutput::VERBOSITY_NORMAL,
            $this->outputDecorated,
            $format
        );
        $this->configureOutputConsole($console);

        return $console;
    }

    /**
     * Returns output instance, prepared to write.
     *
     * @return StreamOutput
     */
    final protected function getWritingConsole()
    {
        if (null === $this->output) {
            $this->output = $this->createOutputConsole();
        }

        return $this->output;
    }
}