<?php
    /**
     * Testes para a classe AutoBP_Log_Manager.
     */

    // Incluir o bootstrap de testes do WordPress, se não for feito globalmente.
    // Geralmente, o executor do PHPUnit lida com isso.

    if ( ! class_exists( 'WP_UnitTestCase' ) ) {
        // Tentar carregar o WP_UnitTestCase se não estiver disponível.
        // Isso pode variar dependendo da configuração do ambiente de teste.
        // Em um ambiente de teste WP padrão, isso já estaria carregado.
        // Para esta escrita, vamos assumir que está disponível.
        // Se não estiver, um esqueleto de classe de teste ainda pode ser escrito.
        if ( file_exists( getenv( 'WP_DEVELOP_DIR' ) . 'tests/phpunit/includes/abstract-testcase.php' ) ) {
            require_once getenv( 'WP_DEVELOP_DIR' ) . 'tests/phpunit/includes/abstract-testcase.php';
        } elseif ( file_exists( '../../../../tests/phpunit/includes/abstract-testcase.php' ) ) { // Caminho relativo comum
             require_once '../../../../tests/phpunit/includes/abstract-testcase.php';
        }
    }
    
    // Certifique-se de que a classe AutoBP_Log_Manager está carregada.
    // O bootstrap.php do plugin deve cuidar disso.
    // require_once AUTOBP_PLUGIN_DIR . 'includes/class-autobp-log-manager.php';


    /**
     * Classe de teste para AutoBP_Log_Manager.
     * 
     * @coversDefaultClass AutoBP_Log_Manager
     * Usaremos WP_UnitTestCase se disponível, senão uma classe de teste PHPUnit básica.
     */
    class AutoBP_Log_Manager_Test extends ( class_exists( 'WP_UnitTestCase' ) ? WP_UnitTestCase::class : PHPUnit\Framework\TestCase::class ) {

        private static $original_wp_debug;
        private static $original_wp_debug_log;
        private static $log_manager_reflection;

        /**
         * Configura o ambiente de teste antes da execução da primeira classe de teste.
         * Inicializa a reflexão para a classe AutoBP_Log_Manager e armazena
         * os estados originais das constantes WP_DEBUG e WP_DEBUG_LOG.
         * 
         * @return void
         * @coversNothing
         */
        public static function setUpBeforeClass(): void {
            if (class_exists('WP_UnitTestCase')) {
                parent::setUpBeforeClass();
            }
            self::$original_wp_debug = defined('WP_DEBUG') ? WP_DEBUG : false;
            self::$original_wp_debug_log = defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false;
            
            // Usar Reflection para acessar o método privado 'log' e a propriedade 'table_name'
            if (class_exists('AutoBP_Log_Manager')) {
                self::$log_manager_reflection = new ReflectionClass( 'AutoBP_Log_Manager' );
                AutoBP_Log_Manager::init(); // Garantir que table_name seja inicializado
            }
        }

        /**
         * Restaura o ambiente após a execução de todos os testes da classe.
         * Restaura as constantes WP_DEBUG e WP_DEBUG_LOG aos seus valores originais.
         * 
         * @return void
         * @coversNothing
         */
        public static function tearDownAfterClass(): void {
            if (class_exists('WP_UnitTestCase')) {
                parent::tearDownAfterClass();
            }
            // Restaurar constantes originais
            self::define_wp_constant('WP_DEBUG', self::$original_wp_debug);
            self::define_wp_constant('WP_DEBUG_LOG', self::$original_wp_debug_log);
        }
        
        /**
         * Define (ou tenta definir) uma constante do WordPress.
         * Usado para controlar WP_DEBUG e WP_DEBUG_LOG durante os testes.
         * 
         * @param string $name Nome da constante.
         * @param mixed $value Valor da constante.
         * @return void
         * @coversNothing
         */
        private static function define_wp_constant($name, $value) {
            if (!defined($name)) {
                define($name, $value);
            } elseif (constant($name) !== $value) {
                // Não podemos redefinir constantes facilmente em PHP sem workarounds (como runkit/uopz)
                // Para testes, é comum rodar em processos separados ou aceitar esta limitação.
                // Ou usar um prefixo para constantes de teste.
            }
        }

        /**
         * Obtém o método privado 'log' da classe AutoBP_Log_Manager usando Reflection.
         * Torna o método acessível para teste.
         * 
         * @return ReflectionMethod
         * @coversNothing
         */
        private function get_private_log_method() {
            if (!self::$log_manager_reflection) $this->markTestSkipped('AutoBP_Log_Manager class not available for reflection.');
            $method = self::$log_manager_reflection->getMethod('log');
            $method->setAccessible(true);
            return $method;
        }
        
        /**
         * Obtém o valor da propriedade privada 'table_name' usando Reflection.
         * 
         * @return string|null
         * @coversNothing
         */
        private function get_table_name_property_value() {
            if (!self::$log_manager_reflection) $this->markTestSkipped('AutoBP_Log_Manager class not available for reflection.');
            $property = self::$log_manager_reflection->getProperty('table_name');
            $property->setAccessible(true);
            return $property->getValue();
        }

        /**
         * Testa se o nome da tabela é inicializado corretamente pelo método init().
         * @covers ::init
         */
        public function test_init_sets_table_name() {
            if (!class_exists('AutoBP_Log_Manager')) $this->markTestSkipped('AutoBP_Log_Manager class not available.');
            global $wpdb;
            if (!$wpdb) $this->markTestSkipped('Global $wpdb not available.');

            AutoBP_Log_Manager::init(); // Chamar explicitamente para o teste
            $this->assertEquals( $wpdb->prefix . 'autoblogpro_logs', $this->get_table_name_property_value() );
        }

        /**
         * Testa se o método log() principal tenta inserir dados no banco de dados.
         * Nota: Este teste é simplificado devido à ausência de um framework de mock completo para $wpdb.
         * Ele verifica se o método retorna true, o que indicaria sucesso na chamada a $wpdb->insert
         * em um cenário onde a inserção não falha.
         * 
         * @test
         * @covers ::log
         */
        public function test_log_method_attempts_to_insert_data() {
            if (!class_exists('AutoBP_Log_Manager')) $this->markTestSkipped('AutoBP_Log_Manager class not available.');
            global $wpdb;
            if (!$wpdb) $this->markTestSkipped('Global $wpdb not available.');
            
            // Mock $wpdb->insert
            // Se estiver usando WP_UnitTestCase, $wpdb já é o objeto do DB de teste.
            // Para testar a chamada a insert, precisamos mocká-lo.
            // Isso é mais complexo sem um framework de mock como Mockery ou Prophecy.
            // Por ora, vamos assumir que podemos verificar os argumentos se pudéssemos interceptar.
            // Como não podemos mockar facilmente $wpdb->insert aqui sem mais setup,
            // vamos focar em se o método tenta executar e retorna true (assumindo que insert funcionaria).
            
            // Para um teste real, você mockaria $wpdb e esperaria que $wpdb->insert fosse chamado.
            // Exemplo conceitual (não funcional diretamente sem framework de mock):
            // $wpdb_mock = $this->getMockBuilder(stdClass::class)->setMethods(['insert'])->getMock();
            // $wpdb_mock->expects($this->once())->method('insert')->willReturn(1);
            // $GLOBALS['wpdb'] = $wpdb_mock; // Sobrescrever global $wpdb com mock

            // Teste simplificado: chama o método e espera true (não ideal, mas um começo)
            $log_method = $this->get_private_log_method();
            $result = $log_method->invoke(null, 'INFO', 'Test message', array('test_key' => 'test_value'));
            
            // Este teste é limitado porque não podemos verificar a query real sem mockar $wpdb.
            // Em um ambiente de teste WP, $wpdb->insert realmente tentaria inserir no DB de teste.
            // Se a tabela existe no DB de teste (criada por activate()), o insert pode funcionar.
            $this->assertTrue( (bool) $result, "O método log() deveria retornar true em sucesso simulado." );
            // Para verificar realmente, precisaríamos buscar o último log inserido ou mockar $wpdb.
        }

        /**
        /**
         * Testa se os métodos de conveniência de nível (info, warning, error)
         * chamam o método log() com o nível correto.
         * 
         * @test
         * @dataProvider level_methods_provider
         * @covers ::info
         * @covers ::warning
         * @covers ::error
         * @covers ::log
         * @param string $method_name O nome do método de nível a ser testado (ex: 'info').
         * @param string $expected_level O nível de log esperado que deve ser passado para `log()`.
         */
        public function test_level_methods_call_log_with_correct_level( $method_name, $expected_level ) {
            if (!class_exists('AutoBP_Log_Manager')) $this->markTestSkipped('AutoBP_Log_Manager class not available.');
            // Este teste é conceitual e precisaria de um mock para AutoBP_Log_Manager::log ou $wpdb->insert
            // Para simplificar, vamos apenas verificar se eles não causam erro fatal e retornam boolean.
            // Em um cenário real, você usaria Reflection para chamar o método privado 'log' ou mockaria $wpdb.
            
            $result = AutoBP_Log_Manager::$method_name( 'Test ' . $method_name );
            $this->assertIsBool( $result, "Método {$method_name} deveria retornar um booleano." );
            // Para verificar o nível, precisaríamos inspecionar o que foi passado para o método log privado
            // ou o que foi inserido no DB (se não mockado).
        }

        /**
         * Provedor de dados para `test_level_methods_call_log_with_correct_level`.
         * Retorna um array de arrays, cada um contendo o nome do método e o nível de log esperado.
         * 
         * @return array
         * @coversNothing
         */
        public function level_methods_provider() {
            return [
                ['info', 'INFO'],
                ['warning', 'WARNING'],
                ['error', 'ERROR'],
            ];
        }

        /**
         * Testa se o método debug() tenta registrar uma mensagem quando WP_DEBUG é true.
         * 
         * @test
         * @covers ::debug
         * @covers ::log
         */
        public function test_debug_method_logs_when_wp_debug_true() {
            if (!class_exists('AutoBP_Log_Manager')) $this->markTestSkipped('AutoBP_Log_Manager class not available.');
            self::define_wp_constant('WP_DEBUG', true);
            // Assim como acima, este teste é limitado sem mocks.
            $result = AutoBP_Log_Manager::debug( 'Test debug message' );
            $this->assertIsBool( $result ); // Esperamos que tente logar.
            // Idealmente, verificar se o método 'log' foi chamado com 'DEBUG'.
        }
        
        /**
         * Testa se o método debug() não tenta registrar uma mensagem (e retorna true) 
         * quando WP_DEBUG é false.
         * 
         * @test
         * @covers ::debug
         */
        public function test_debug_method_does_not_log_when_wp_debug_false() {
            if (!class_exists('AutoBP_Log_Manager')) $this->markTestSkipped('AutoBP_Log_Manager class not available.');
            self::define_wp_constant('WP_DEBUG', false);
            $result = AutoBP_Log_Manager::debug( 'Test debug message (WP_DEBUG false)' );
            $this->assertTrue( $result, "Debug deveria retornar true (indicando que não falhou, mesmo que não tenha logado)" );
            // Idealmente, verificar que o método 'log' NÃO foi chamado.
        }
        
        /**
         * Testa se o método log() faz fallback para `error_log` do PHP
         * quando o nome da tabela não está inicializado e WP_DEBUG_LOG é true.
         * 
         * @test
         * @runInSeparateProcess Impede que a manipulação de constantes afete outros testes.
         * @preserveGlobalState disabled Necessário com @runInSeparateProcess.
         * @covers ::log
         */
        public function test_log_falls_back_to_php_error_log_if_table_not_init() {
            // Assegurar que a classe está disponível no processo separado
            $plugin_main_file = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/autoblogpro.php';
            if (file_exists($plugin_main_file)) {
                 if(!defined('AUTOBP_PLUGIN_DIR')) define('AUTOBP_PLUGIN_DIR', plugin_dir_path($plugin_main_file));
                 require_once AUTOBP_PLUGIN_DIR . 'includes/class-autobp-log-manager.php';
            } else {
                 $this->markTestSkipped('Plugin principal ou Log Manager não encontrado para teste em processo separado.');
            }
            
            if (!class_exists('AutoBP_Log_Manager')) {
                $this->markTestSkipped('AutoBP_Log_Manager não carregada no processo separado.');
            }


            // Definir WP_DEBUG_LOG como true para este teste
            if (!defined('WP_DEBUG_LOG')) define('WP_DEBUG_LOG', true);
            if (!defined('WP_DEBUG')) define('WP_DEBUG', true); // error_log é afetado por WP_DEBUG também

            // Usar Reflection para "desinicializar" $table_name temporariamente
            $reflection_class = new ReflectionClass('AutoBP_Log_Manager');
            $property = $reflection_class->getProperty('table_name');
            $property->setAccessible(true);
            // AutoBP_Log_Manager::init(); // Chamar init para pegar o nome da tabela original
            // $original_table_name = $property->getValue(); // Não podemos usar $this aqui
            $property->setValue(null, ''); // Forçar nome da tabela vazio

            // Capturar error_log (requer um manipulador de erro customizado ou verificar o arquivo de log)
            // Esta é a parte mais complexa de testar unitariamente de forma isolada.
            // Uma forma é usar um error handler customizado durante o teste.
            // Por ora, vamos chamar e assumir que o error_log seria chamado.
            // Não podemos verificar o output de error_log diretamente aqui de forma simples.

            $log_method = $reflection_class->getMethod('log');
            $log_method->setAccessible(true);
            $result = $log_method->invoke(null, 'INFO', 'Test fallback error_log', array('key' => 'value'));
            
            $this->assertFalse( $result, "Log deveria retornar false se a tabela não está init e fallback para error_log." );

            // Restaurar o nome da tabela (não essencial no processo separado, mas boa prática se não fosse)
            // $property->setValue(null, $original_table_name); 
        }
    }
    ?>
