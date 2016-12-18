<?php
namespace AppBundle\Bridge;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Behat\Definition\ServiceContainer\DefinitionExtension;
use Behat\Behat\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Behat\Gherkin\ServiceContainer\GherkinExtension;
use Behat\Behat\Hook\ServiceContainer\HookExtension;
use Behat\Behat\Output\Printer\Formatter\ConsoleFormatter;
use Behat\Behat\Output\ServiceContainer\Formatter\JUnitFormatterFactory;
use Behat\Behat\Output\ServiceContainer\Formatter\PrettyFormatterFactory;
use Behat\Behat\Output\ServiceContainer\Formatter\ProgressFormatterFactory;
use Behat\Behat\Snippet\ServiceContainer\SnippetExtension;
use Behat\Behat\Tester\ServiceContainer\TesterExtension;
use Behat\Behat\Transformation\ServiceContainer\TransformationExtension;
use Behat\Behat\Translator\ServiceContainer\GherkinTranslationsExtension;

use Behat\Testwork\Argument\ServiceContainer\ArgumentExtension;
use Behat\Testwork\Autoloader\ServiceContainer\AutoloaderExtension;
use Behat\Testwork\Call\ServiceContainer\CallExtension;
use Behat\Testwork\Cli\ServiceContainer\CliExtension;
use Behat\Testwork\Environment\ServiceContainer\EnvironmentExtension;
use Behat\Testwork\Exception\ServiceContainer\ExceptionExtension;
use Behat\Testwork\Filesystem\ServiceContainer\FilesystemExtension;
use Behat\Testwork\Ordering\OrderedExercise;
use Behat\Testwork\Ordering\ServiceContainer\OrderingExtension;
use Behat\Testwork\Output\Printer\Factory\OutputFactory;
use Behat\Testwork\Output\ServiceContainer\Formatter\FormatterFactory;
use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Behat\Testwork\ServiceContainer\Configuration\ConfigurationLoader;
use Behat\Testwork\ServiceContainer\ContainerLoader;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Behat\Testwork\ServiceContainer\ServiceProcessor;
use Behat\Testwork\Specification\ServiceContainer\SpecificationExtension;
use Behat\Testwork\Suite\ServiceContainer\SuiteExtension;
use Behat\Testwork\Suite\SuiteRegistry;
use Behat\Testwork\Tester\Result\IntegerTestResult;
use Behat\Testwork\Tester\Result\TestWithSetupResult;
use Behat\Testwork\Translator\ServiceContainer\TranslatorExtension;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\BufferedOutput;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;


class MyOutputFactory extends OutputFactory
{

    /** @var  OutputInterface */
    public $output;

    public function __construct($out)
    {
        $this->output = $out;
    }

    /**
     * @return OutputInterface
     */
    public function createOutput()
    {
        return $this->output;
    }
}


class MyPrettyFormatterFactory extends PrettyFormatterFactory
{


    public function __construct(ServiceProcessor $processor = null)
    {
        parent::__construct($processor);
    }

    /**
     * Creates output printer definition.
     *
     * @return Definition
     */
    protected function createOutputPrinterDefinition()
    {
        return new Definition(
            'Behat\Testwork\Output\Printer\StreamOutputPrinter', array(
                new Definition(
                    'AppBundle\Bridge\myOutputFactory',
                    array(new Reference('cli.output'))
                ),
            )
        );
    }
}


class AppBehat
{


    const NAME = 'appbehat';
    const ENV_NAME = 'APPBEHAT_PARAMS';


    public $rootPath;


    /**
     * @var ExtensionManager
     */
    private $extensionManager;
    private $controllers = array();


    public function __construct()
    {

    }


    public function getDefaultStyles()
    {
        return array(
            'keyword' => new OutputFormatterStyle('black', 'white', array('bold')),
            'stdout' => new OutputFormatterStyle('black', 'white', array()),
            'exception' => new OutputFormatterStyle('red', 'white'),
            'undefined' => new OutputFormatterStyle('yellow', 'white'),
            'pending' => new OutputFormatterStyle('yellow', 'white'),
            'pending_param' => new OutputFormatterStyle('yellow', 'white' ,array('bold')),
            'failed'        => new OutputFormatterStyle('red', 'white'),
            'failed_param'  => new OutputFormatterStyle('red', 'white', array('bold')),
            'passed'        => new OutputFormatterStyle('green', 'white'),
            'passed_param'  => new OutputFormatterStyle('green', 'white', array('bold')),
            'skipped'       => new OutputFormatterStyle('cyan', 'white'),
            'skipped_param' => new OutputFormatterStyle('cyan', 'white', array('bold')),
            'comment'       => new OutputFormatterStyle('black', 'white'),
            'tag'           => new OutputFormatterStyle('cyan', 'white')
        );


        $formatter = new ConsoleFormatter($this->isOutputDecorated());

        foreach ($this->getDefaultStyles() as $name => $style) {
            $formatter->setStyle($name, $style);
        }

    }


    public function init($rootPath)
    {

        $input = null;
        $output = new BufferedOutput(
            BufferedOutput::VERBOSITY_NORMAL,
            true,
            new ConsoleFormatter(true, $this->getDefaultStyles())
        );


        $this->rootPath = $rootPath;


        $container = $this->createContainer($input, $output);

        /** @var SuiteRegistry $suiteRegistry */

        $suiteRegistry = $container->get('suite.registry');
        $config = $container->getParameter('suite.configurations');

        $suiteRegistry->registerSuiteConfiguration('default', null, $config['default']['settings']);

        $suites = $suiteRegistry->getSuites();

        $specificationsFinder = $container->get('specifications.finder');
        $specs = $specificationsFinder->findSuitesSpecifications($suites, null);
        $testerExercise = $container->get('tester.exercise');
        $testerSuite = $container->get('tester.suite');
        $testerSpecification = $container->get('tester.specification');
        $classLoader = $container->get('class_loader');
        $classLoader->register();
        $outputManager = $container->get('output.manager');
        $skip = false;
        /** @var OrderedExercise $testerExercise */
        $setup = $testerExercise->setUp($specs, $skip);


        $skip = !$setup->isSuccessful() || $skip;
        $testResult = $testerExercise->test($specs, $skip);
        $teardown = $testerExercise->tearDown($specs, $skip, $testResult);

        $result = new IntegerTestResult($testResult->getResultCode());

        $result = new TestWithSetupResult($setup, $result, $teardown);
        $converter = new AnsiToHtmlConverter();
        $buffer = $output->fetch();
        $data = $converter->convert($buffer);
        $data = nl2br($data);

        return $data;
    }


    /**
     * Creates container instance, loads extensions and freezes it.
     *
     *
     * @return ContainerInterface
     */
    private function createContainer($input, $output)
    {

        $container = new ContainerBuilder();

        $container->setParameter('cli.command.name', self::NAME);
        $container->setParameter('paths.base', $this->rootPath);

        $container->set('cli.input', $input);
        $container->set('cli.output', $output);
        $this->controllers = $this->getDefaultExtensions();
        $this->extensionManager = new ExtensionManager($this->controllers);
        $extension = new ContainerLoader($this->extensionManager);
        $configurationLoader = new ConfigurationLoader(self::ENV_NAME, $this->getConfigPath());
        $configuration = $configurationLoader->loadConfiguration();
        $extension->load($container, $configuration);
        $container->addObjectResource($extension);
        $container->compile();

        return $container;
    }

    protected function getConfigPath()
    {

        $paths = array_filter(
            array(
                $this->rootPath.DIRECTORY_SEPARATOR.'behat.yml',
                $this->rootPath.DIRECTORY_SEPARATOR.'behat.yml.dist',
                $this->rootPath.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'behat.yml',
                $this->rootPath.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'behat.yml.dist',
            ),
            'is_file'
        );

        if (count($paths)) {
            return current($paths);
        }

        return null;
    }


    /**
     * {@inheritdoc}
     */
    protected function getDefaultExtensions()
    {
        $processor = new ServiceProcessor();

        return array(
            new ArgumentExtension(),
            new AutoloaderExtension(array('' => '%paths.base%/features/bootstrap')),
            new SuiteExtension($processor),
            new OutputExtension('pretty', $this->getDefaultFormatterFactories($processor), $processor),
            new ExceptionExtension($processor),
            new GherkinExtension($processor),
            new CallExtension($processor),
            new TranslatorExtension(),
            new GherkinTranslationsExtension(),
            new TesterExtension($processor),
            new CliExtension($processor),
            new EnvironmentExtension($processor),
            new SpecificationExtension($processor),
            new FilesystemExtension(),
            new ContextExtension($processor),
            new SnippetExtension($processor),
            new DefinitionExtension($processor),
            new EventDispatcherExtension($processor),
            new HookExtension(),
            new TransformationExtension($processor),
            new OrderingExtension($processor),
        );
    }


    /**
     * Returns default formatter factories.
     *
     * @param ServiceProcessor $processor
     *
     * @return FormatterFactory[]
     */
    private function getDefaultFormatterFactories(ServiceProcessor $processor)
    {
        return array(
            new MyPrettyFormatterFactory($processor),
            //         new PrettyFormatterFactory($processor),
            new ProgressFormatterFactory($processor),
            new JUnitFormatterFactory(),
        );
    }


}

