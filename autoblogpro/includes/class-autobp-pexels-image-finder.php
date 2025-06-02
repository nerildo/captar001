<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AutoBP_Pexels_Image_Finder' ) ) {
    /**
     * Classe AutoBP_Pexels_Image_Finder.
     *
     * Responsável por interagir com a API Pexels para buscar imagens.
     * Esta classe lida com a construção de requisições para a API Pexels,
     * o envio dessas requisições autenticadas com a chave de API fornecida,
     * e o processamento da resposta JSON para retornar uma lista formatada de imagens
     * ou um objeto WP_Error em caso de falha.
     *
     * @package     AutoBlogPro
     * @subpackage  Includes
     * @since       0.2.0
     */
    class AutoBP_Pexels_Image_Finder {

        /**
         * Chave da API Pexels.
         * @since 0.2.0
         * @access private
         * @var string
         */
        private $api_key;

        /**
         * URL base da API Pexels.
         * @since 0.2.0
         * @access private
         * @var string
         */
        private $api_url = 'https://api.pexels.com/v1/';

        /**
         * Construtor da classe AutoBP_Pexels_Image_Finder.
         *
         * Armazena a chave da API Pexels fornecida, removendo quaisquer espaços em branco
         * no início ou no fim.
         *
         * @since 0.2.0
         * @access public
         * @param string $api_key Chave da API Pexels.
         */
        public function __construct( $api_key ) {
            $this->api_key = trim( $api_key );
        }

        /**
         * Busca imagens na API Pexels com base em uma query e outros parâmetros.
         *
         * Constrói e executa uma requisição GET para o endpoint 'search' da API Pexels.
         * Valida a presença da API key e da query de busca.
         * Inclui parâmetros como número de imagens por página, orientação e tamanho.
         * Utiliza `wp_remote_get` para a chamada HTTP, incluindo o cabeçalho de Autorização.
         *
         * Em caso de sucesso, processa a resposta JSON, extrai os dados relevantes de cada foto
         * (ID, fotógrafo, URLs das imagens em diferentes tamanhos, URL da página da foto na Pexels,
         * texto alternativo) e formata em um array.
         * Em caso de erro (seja na chamada HTTP ou erro retornado pela API Pexels),
         * retorna um objeto `WP_Error` com uma mensagem descritiva.
         *
         * Loga as etapas do processo usando `AutoBP_Log_Manager`.
         *
         * @since 0.2.0
         * @access public
         * @param string $query Palavras-chave para a busca de imagens.
         * @param int    $per_page Número de imagens a serem retornadas por página (Pexels default: 15, max: 80).
         *                         Este método usa 10 por padrão.
         * @param string $orientation Orientação desejada das imagens (landscape, portrait, square). Default: 'landscape'.
         * @param string $size Tamanho mínimo das imagens (large, medium, small). Pexels recomenda não usar para
         *                     melhores resultados, mas pode ser útil. Default: 'medium'.
         * @return array|WP_Error Um array de objetos de imagem formatados em caso de sucesso,
         *                        ou um objeto `WP_Error` em caso de falha. Cada objeto de imagem contém:
         *                        'id', 'photographer', 'photographer_url', 'avg_color', 'src' (array com URLs),
         *                        'alt', 'pexels_url'.
         */
        public function search_images( $query, $per_page = 10, $orientation = 'landscape', $size = 'medium' ) {
            if ( empty( $this->api_key ) ) {
                return new WP_Error( 'pexels_api_key_missing', __( 'Chave da API Pexels não configurada.', 'autoblogpro' ) );
            }
            if ( empty( $query ) ) {
                return new WP_Error( 'pexels_query_missing', __( 'Nenhuma palavra-chave fornecida para busca de imagens.', 'autoblogpro' ) );
            }

            $request_url = add_query_arg( array(
                'query'       => rawurlencode( $query ),
                'per_page'    => intval( $per_page ),
                'orientation' => sanitize_key( $orientation ),
                'size'        => sanitize_key( $size ),
            ), $this->api_url . 'search' );

            $args = array(
                'headers' => array(
                    'Authorization' => $this->api_key,
                ),
                'timeout' => 30,
            );

            AutoBP_Log_Manager::info( 'Buscando imagens na Pexels.', array( 'url' => $request_url, 'query' => $query ) );
            $response = wp_remote_get( $request_url, $args );

            if ( is_wp_error( $response ) ) {
                AutoBP_Log_Manager::error( 'Erro em wp_remote_get para Pexels: ' . $response->get_error_message(), array( 'query' => $query ) );
                return $response;
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );

            if ( $response_code !== 200 ) {
                $error_data = json_decode( $response_body, true );
                $message = isset( $error_data['error'] ) ? $error_data['error'] : __( 'Erro desconhecido da API Pexels.', 'autoblogpro' );
                AutoBP_Log_Manager::error( 'Erro da API Pexels: ' . $message, array( 'code' => $response_code, 'body' => $response_body, 'query' => $query ) );
                return new WP_Error( 'pexels_api_error', $message, array( 'status' => $response_code ) );
            }

            $data = json_decode( $response_body, true );

            if ( ! isset( $data['photos'] ) || ! is_array( $data['photos'] ) ) {
                AutoBP_Log_Manager::warning( 'Resposta da API Pexels não contém "photos" ou não é um array.', array( 'body' => $response_body, 'query' => $query ) );
                return new WP_Error( 'pexels_invalid_response', __( 'Resposta inválida da API Pexels.', 'autoblogpro' ) );
            }
            
            // Mapear para uma estrutura mais simples se necessário, ex: id, photograper, src (medium, large, original), page_url
            $images = array();
            foreach ( $data['photos'] as $photo ) {
                $images[] = array(
                    'id'           => $photo['id'],
                    'photographer' => $photo['photographer'],
                    'photographer_url' => $photo['photographer_url'],
                    'avg_color'    => $photo['avg_color'],
                    'src'          => $photo['src'], // Contém: original, large2x, large, medium, small, portrait, landscape, tiny
                    'alt'          => !empty($photo['alt']) ? $photo['alt'] : sprintf( __('Foto por %s em Pexels', 'autoblogpro'), $photo['photographer'] ),
                    'pexels_url'   => $photo['url'] // Link para a página da foto em Pexels
                );
            }
            AutoBP_Log_Manager::info( sprintf( 'Encontradas %d imagens na Pexels para a query "%s".', count( $images ), $query ), array( 'query' => $query, 'count' => count( $images ) ) );
            return $images;
        }
    }
}
?>
