<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AutoBP_Copyscape_Checker' ) ) {
    /**
     * Classe para interagir com a API Copyscape.
     */
    /**
     * Classe para interagir com a API Copyscape para verificação de plágio.
     *
     * Esta classe lida com a construção de requisições para a API Copyscape,
     * o envio dessas requisições, e o processamento das respostas (JSON ou XML para erros).
     * Retorna dados estruturados sobre os resultados da verificação ou um objeto WP_Error em caso de falha.
     *
     * @package     AutoBlogPro
     * @subpackage  Includes
     * @since       0.1.0
     */
    class AutoBP_Copyscape_Checker {

        /**
         * Nome de usuário da conta Copyscape.
         * @since 0.1.0
         * @access private
         * @var string
         */
        private $username;

        /**
         * Chave da API Copyscape.
         * @since 0.1.0
         * @access private
         * @var string
         */
        private $api_key;

        /**
         * O texto a ser verificado por plágio.
         * @since 0.1.0
         * @access private
         * @var string
         */
        private $text_to_check;

        /**
         * URL base para a API Copyscape.
         * @since 0.1.0
         * @access private
         * @var string
         */
        private $api_url = 'https://www.copyscape.com/api/';

        /**
         * Construtor da classe AutoBP_Copyscape_Checker.
         *
         * Armazena as credenciais da API Copyscape (nome de usuário e chave).
         *
         * @since 0.1.0
         * @access public
         * @param string $username Nome de usuário Copyscape.
         * @param string $api_key Chave da API Copyscape.
         */
        public function __construct( $username, $api_key ) {
            $this->username = trim( $username );
            $this->api_key = trim( $api_key );
        }

        /**
         * Define o texto que será enviado para a API Copyscape para verificação.
         *
         * @since 0.1.0
         * @access public
         * @param string $text O texto (conteúdo do post) para verificação de plágio.
         * @return $this Permite encadeamento de métodos.
         */
        public function set_text( $text ) {
            $this->text_to_check = $text;
            return $this;
        }

        /**
         * Realiza a verificação de plágio enviando o texto para a API Copyscape.
         *
         * Constrói a requisição com os parâmetros necessários (usuário, chave, operação, formato, codificação, texto).
         * Usa `wp_remote_post()` para se comunicar com a API.
         * Processa a resposta JSON (ou XML para certos erros) e trata possíveis erros de comunicação ou da API.
         *
         * Parâmetros da API Copyscape utilizados:
         * - `u`: Nome de usuário.
         * - `k`: Chave da API.
         * - `o`: Operação ('csearch' para busca na web).
         * - `f`: Formato da resposta ('json').
         * - `e`: Codificação ('UTF-8').
         * - `t`: Texto a ser verificado.
         *
         * @since 0.1.0
         * @access public
         * @return array|WP_Error Um array com os resultados da verificação em caso de sucesso,
         *                        contendo chaves como 'status', 'query_words', 'count', 'results',
         *                        'all_matches_shown', 'all_available', 'cost'.
         *                        Retorna um objeto `WP_Error` em caso de falha na validação,
         *                        erro de comunicação, ou erro retornado pela API Copyscape.
         */
        public function check() {
            $text_hash_for_log = !empty($this->text_to_check) ? md5($this->text_to_check) : 'empty_text';
            AutoBP_Log_Manager::info( 
                'Iniciando verificação Copyscape.', 
                array( 
                    'username' => $this->username, 
                    'text_length' => strlen($this->text_to_check),
                    'text_hash' => $text_hash_for_log 
                ) 
            );

            // Validação das credenciais e do texto a ser verificado.
            if ( empty( $this->username ) || empty( $this->api_key ) ) {
                AutoBP_Log_Manager::error( 'Verificação Copyscape falhou: Credenciais ausentes.', array('username_set' => !empty($this->username), 'apikey_set' => !empty($this->api_key)) );
                return new WP_Error( 'copyscape_credentials_missing', __( 'Nome de usuário ou chave da API Copyscape não configurados.', 'autoblogpro' ) );
            }

            if ( empty( $this->text_to_check ) ) {
                AutoBP_Log_Manager::warning( 'Verificação Copyscape: Nenhum texto fornecido.', array('username' => $this->username) );
                return new WP_Error( 'copyscape_text_missing', __( 'Nenhum texto fornecido para verificação de plágio.', 'autoblogpro' ) );
            }

            // Validações de tamanho do texto (baseadas em requisitos comuns da API Copyscape).
            $text_length = strlen( $this->text_to_check );
            if ( $text_length < 100 ) { 
                AutoBP_Log_Manager::warning( 'Verificação Copyscape: Texto muito curto.', array('username' => $this->username, 'text_length' => $text_length) );
                return new WP_Error( 'copyscape_text_too_short', __( 'O texto é muito curto para uma verificação significativa (geralmente são necessárias pelo menos 20 palavras ou ~100 caracteres).', 'autoblogpro' ) );
            }
            if ( $text_length > 80000 ) { 
                AutoBP_Log_Manager::warning( 'Verificação Copyscape: Texto muito longo.', array('username' => $this->username, 'text_length' => $text_length) );
                return new WP_Error( 'copyscape_text_too_long', __( 'O texto é muito longo para uma única verificação na API Copyscape (limite prático de ~80.000 caracteres). Considere dividir o texto.', 'autoblogpro' ) );
            }

            // Parâmetros para a requisição à API Copyscape.
            $api_params = array(
                'u' => $this->username,
                'k' => $this->api_key,
                'o' => 'csearch', 
                'f' => 'json',    
                'e' => 'UTF-8',   
            );
            
            $request_url = $this->api_url; 
            
            $post_body = $api_params;
            $post_body['t'] = $this->text_to_check; // Texto é adicionado ao corpo do POST.

            // Argumentos para wp_remote_post.
            $args = array(
                'body'    => $post_body, 
                'timeout' => 90,        // Timeout estendido para a chamada da API.
                'headers' => array(),   // Copyscape usa parâmetros no corpo para auth, não headers Bearer.
                                        // 'Content-Type' é definido como 'application/x-www-form-urlencoded' por padrão por wp_remote_post para body array.
            );

            // Realiza a chamada à API.
            $response = wp_remote_post( $request_url, $args );

            // Processamento da resposta.
            if ( is_wp_error( $response ) ) {
                AutoBP_Log_Manager::error( 'Falha na chamada wp_remote_post para API Copyscape: ' . $response->get_error_message(), array( 'username' => $this->username, 'error_code' => $response->get_error_code(), 'text_hash' => $text_hash_for_log ) );
                return new WP_Error( 'copyscape_wp_remote_post_failed', sprintf( __( 'Falha na comunicação com a API Copyscape: %s', 'autoblogpro' ), $response->get_error_message() ) );
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );

            if ( empty($response_body) ) {
                AutoBP_Log_Manager::error( 'Resposta vazia da API Copyscape.', array( 'username' => $this->username, 'response_code' => $response_code, 'text_hash' => $text_hash_for_log ) );
                return new WP_Error( 'copyscape_empty_response_body', sprintf(__( 'A API Copyscape retornou uma resposta vazia (HTTP %d). Verifique o status da API Copyscape.', 'autoblogpro' ), $response_code ));
            }

            $data = json_decode( $response_body, true ); 

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                AutoBP_Log_Manager::error( 'Falha ao decodificar JSON da API Copyscape: ' . json_last_error_msg(), array( 'username' => $this->username, 'response_body_snippet' => mb_substr($response_body, 0, 250), 'text_hash' => $text_hash_for_log ) );
                if ( strpos( trim($response_body), '<errorresponse>' ) === 0 || strpos( trim($response_body), '<error>' ) !== false ) {
                    try {
                        $xml = @simplexml_load_string( $response_body ); 
                        if ( $xml && isset($xml->error) ) {
                            AutoBP_Log_Manager::error( 'Erro XML da API Copyscape: ' . (string) $xml->error, array( 'username' => $this->username, 'xml_error' => (string) $xml->error, 'text_hash' => $text_hash_for_log ) );
                            return new WP_Error( 'copyscape_api_xml_error', sprintf(__( 'Erro da API Copyscape: %s', 'autoblogpro' ), (string) $xml->error) );
                        }
                    } catch (Exception $e) {
                        AutoBP_Log_Manager::error( 'Exceção ao tentar parsear erro XML do Copyscape: ' . $e->getMessage(), array( 'username' => $this->username, 'text_hash' => $text_hash_for_log ) );
                    }
                }
                return new WP_Error( 'copyscape_json_decode_failed', sprintf(__( 'Falha ao decodificar a resposta da API Copyscape (esperado JSON). Erro: %1$s. Resposta recebida (trecho): %2$s', 'autoblogpro' ), json_last_error_msg(), esc_html(substr($response_body, 0, 250)) ) );
            }

            if ( isset( $data['error'] ) ) {
                AutoBP_Log_Manager::error( 'Erro retornado pela API Copyscape: ' . $data['error'], array( 'username' => $this->username, 'api_error' => $data['error'], 'text_hash' => $text_hash_for_log ) );
                return new WP_Error( 'copyscape_api_error_message', sprintf( __( 'Erro retornado pela API Copyscape: %s', 'autoblogpro' ), sanitize_text_field($data['error']) ) );
            }
            
            if ( $response_code !== 200 && !isset($data['querywords']) ) { 
                 AutoBP_Log_Manager::error( 'Erro HTTP da API Copyscape sem mensagem de erro clara.', array( 'username' => $this->username, 'response_code' => $response_code, 'response_body_snippet' => mb_substr($response_body,0,250), 'text_hash' => $text_hash_for_log ) );
                 return new WP_Error( 'copyscape_http_error_unknown', sprintf(__( 'Erro HTTP %s ao contatar a API Copyscape, e a resposta não continha uma mensagem de erro clara da Copyscape. Resposta (trecho): %s', 'autoblogpro' ), $response_code, esc_html(substr($response_body,0,250))) );
            }
            
            if ( !isset($data['count']) || !isset($data['result']) ) { // 'result' é o nome do array de resultados na API, não 'results'
                 AutoBP_Log_Manager::error( 'Estrutura de resposta inesperada da API Copyscape.', array( 'username' => $this->username, 'response_data_keys' => array_keys($data), 'text_hash' => $text_hash_for_log ) );
                 return new WP_Error( 'copyscape_unexpected_response_structure', __( 'Resposta inesperada da API Copyscape. A estrutura de dados de sucesso não foi reconhecida.', 'autoblogpro' ) );
            }

            AutoBP_Log_Manager::info( 'Verificação Copyscape bem-sucedida.', array( 'username' => $this->username, 'result_count' => $data['count'], 'cost' => $data['cost'], 'query_words' => $data['querywords'], 'text_hash' => $text_hash_for_log ) );
            return array(
                'status'            => 'success', // Indica sucesso interno.
                'query_words'       => isset($data['querywords']) ? intval($data['querywords']) : 0, // Número de palavras na consulta.
                'count'             => intval($data['count']), // Número de resultados de plágio encontrados.
                'results'           => isset($data['result']) && is_array($data['result']) ? $data['result'] : array(), // Array detalhado dos resultados.
                'all_matches_shown' => isset($data['allavailableshown']) ? (bool)$data['allavailableshown'] : false, // Se todos os resultados disponíveis foram mostrados.
                'all_available'     => isset($data['allavailable']) ? (bool)$data['allavailable'] : false, // Se todos os resultados estão disponíveis (pode haver mais se 'count' for alto).
                'cost'              => isset($data['cost']) ? floatval($data['cost']) : 0.0, // Custo da pesquisa em créditos Copyscape.
            );
        }

        // Outros métodos auxiliares podem ser adicionados conforme necessário (ex: para construir URL da API, processar XML).
    }
}
?>
