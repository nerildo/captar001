<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AutoBP_Log_Manager' ) ) {
    /**
     * Classe para gerenciar logs do plugin AutoBlogPro.
     * 
     * Esta classe fornece métodos estáticos para registrar mensagens de log em uma
     * tabela personalizada do banco de dados (`wp_autoblogpro_logs`).
     * Suporta diferentes níveis de log (INFO, WARNING, ERROR, DEBUG) e permite
     * armazenar dados contextuais em formato JSON (com fallback para serialização).
     * A inicialização (`init()`) deve ser chamada cedo no carregamento do plugin
     * para definir o nome da tabela. Se o logging for tentado antes da inicialização
     * ou se a tabela não estiver acessível, ele fará um fallback para `error_log` do PHP
     * se `WP_DEBUG_LOG` estiver ativo.
     *
     * Estrutura da tabela de logs:
     * - `log_id` (BIGINT): ID auto-incrementável.
     * - `log_timestamp` (DATETIME): Timestamp GMT/UTC do log.
     * - `log_level` (VARCHAR): Nível do log (INFO, WARNING, ERROR, DEBUG).
     * - `log_message` (TEXT): A mensagem de log.
     * - `log_context` (LONGTEXT): Dados contextuais (JSON ou serializados).
     *
     * @package     AutoBlogPro
     * @subpackage  Includes
     * @since       0.2.0
     */
    class AutoBP_Log_Manager {

        /**
         * Nome da tabela de logs, prefixado.
         * @since 0.2.0
         * @access private
         * @var string
         */
        private static $table_name = '';

        /**
         * Inicializa o nome da tabela de logs.
         * 
         * Este método deve ser chamado uma vez durante a inicialização do plugin
         * (ex: no construtor da classe principal do plugin) para configurar o 
         * nome completo da tabela de logs, incluindo o prefixo do WordPress.
         * 
         * @since 0.2.0
         * @access public
         * @global wpdb $wpdb Objeto de acesso ao banco de dados do WordPress.
         */
        public static function init() {
            global $wpdb;
            self::$table_name = $wpdb->prefix . 'autoblogpro_logs';
        }

        /**
         * Adiciona uma entrada de log ao banco de dados.
         * 
         * Método privado central que é chamado pelos métodos de nível público (info, error, etc.).
         * Insere os dados na tabela de logs. Se o nome da tabela não foi inicializado via `init()`,
         * tenta um fallback para `error_log` do PHP se `WP_DEBUG_LOG` estiver habilitado.
         * O contexto é armazenado como uma string JSON, com fallback para serialização se a
         * codificação JSON falhar.
         *
         * @since 0.2.0
         * @access private
         * @global wpdb $wpdb Objeto de acesso ao banco de dados do WordPress.
         * @param string $level   Nível do log (INFO, ERROR, WARNING, DEBUG).
         * @param string $message A mensagem principal do log.
         * @param array  $context Dados contextuais adicionais (opcional).
         * @return bool True se o log foi inserido com sucesso no DB ou fallback para error_log teve sucesso (ou se não logou debug), false caso contrário.
         */
        private static function log( $level, $message, $context = array() ) {
            if ( empty( self::$table_name ) ) {
                // Se não inicializado, não podemos logar.
                // Isso pode acontecer se log() for chamado muito cedo.
                // Considerar um fallback para error_log do PHP se a tabela não estiver pronta.
                if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log("[AutoBlogPro DBLog Uninitialized] [$level] $message - Context: " . print_r($context, true)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                }
                return false;
            }

            global $wpdb;

            $data = array(
                'log_timestamp' => current_time( 'mysql', true ), // GMT/UTC time
                'log_level'     => strtoupper( $level ),
                'log_message'   => $message,
            );
            $format = array( '%s', '%s', '%s' );

            if ( ! empty( $context ) ) {
                // Tentar serializar/json_encode o contexto. JSON é geralmente melhor.
                $context_serialized = wp_json_encode( $context );
                if (false === $context_serialized) {
                     // Fallback se json_encode falhar (ex: recursos ou objetos complexos)
                    $context_serialized = serialize( $context );
                }
                $data['log_context'] = $context_serialized;
                $format[] = '%s';
            } else {
                // Garantir que log_context tenha um valor default se não houver contexto,
                // para evitar problemas com o $wpdb->insert se a coluna não permitir NULL
                // e não tiver um default na DB (embora a nossa permita NULL implicitamente).
                $data['log_context'] = null; 
                $format[] = '%s'; // ou null e não adicionar ao format? '%s' para null deve funcionar.
            }


            $result = $wpdb->insert( self::$table_name, $data, $format );
            
            return (bool) $result;
        }

        /**
         * Registra uma mensagem de log com nível INFO.
         *
         * @since 0.2.0
         * @access public
         * @param string $message A mensagem a ser registrada.
         * @param array  $context Dados contextuais adicionais (opcional).
         * @return bool Resultado da operação de log.
         */
        public static function info( $message, $context = array() ) {
            return self::log( 'INFO', $message, $context );
        }

        /**
         * Registra uma mensagem de log com nível WARNING.
         *
         * @since 0.2.0
         * @access public
         * @param string $message A mensagem a ser registrada.
         * @param array  $context Dados contextuais adicionais (opcional).
         * @return bool Resultado da operação de log.
         */
        public static function warning( $message, $context = array() ) {
            return self::log( 'WARNING', $message, $context );
        }

        /**
         * Registra uma mensagem de log com nível ERROR.
         *
         * @since 0.2.0
         * @access public
         * @param string $message A mensagem a ser registrada.
         * @param array  $context Dados contextuais adicionais (opcional).
         * @return bool Resultado da operação de log.
         */
        public static function error( $message, $context = array() ) {
            return self::log( 'ERROR', $message, $context );
        }

        /**
         * Registra uma mensagem de log com nível DEBUG.
         * 
         * Mensagens de debug são registradas apenas se `WP_DEBUG` estiver definido como true.
         *
         * @since 0.2.0
         * @access public
         * @param string $message A mensagem a ser registrada.
         * @param array  $context Dados contextuais adicionais (opcional).
         * @return bool True se o log foi registrado ou se o debug não está ativo, false em falha de inserção.
         */
        public static function debug( $message, $context = array() ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                return self::log( 'DEBUG', $message, $context );
            }
            return true; 
        }
    }
}
?>
