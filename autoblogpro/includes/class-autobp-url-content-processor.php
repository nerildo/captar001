<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AutoBP_URL_Content_Processor' ) ) {
    /**
     * Classe para buscar e processar conteúdo de uma URL.
     * 
     * Fetches HTML de uma URL e tenta extrair o conteúdo textual principal.
     * Utiliza DOMDocument para análise HTML básica.
     * 
     * @package     AutoBlogPro
     * @subpackage  Includes
     * @since       0.1.0
     */
    class AutoBP_URL_Content_Processor {
        /**
         * A URL a ser processada.
         * @since 0.1.0
         * @access private
         * @var string
         */
        private $url;

        /**
         * O conteúdo HTML bruto obtido da URL.
         * @since 0.1.0
         * @access private
         * @var string|null
         */
        private $raw_html_content;

        /**
         * O conteúdo textual principal extraído do HTML.
         * @since 0.1.0
         * @access private
         * @var string|null
         */
        private $extracted_text_content;

        /**
         * Palavras-chave identificadas pela análise semântica.
         * @since 0.1.0
         * @access private
         * @var array
         */
        private $identified_keywords = array();

        /**
         * Tópicos principais identificados pela análise semântica.
         * @since 0.1.0
         * @access private
         * @var array
         */
        private $identified_topics = array();

        /**
         * Insights adicionais ou pontos de discussão gerados pela IA.
         * @since 0.1.0
         * @access private
         * @var array
         */
        private $additional_insights = array();

        /**
         * Construtor da classe.
         *
         * Valida a URL fornecida e a armazena.
         *
         * @since 0.1.0
         * @access public
         * @param string $url A URL do artigo de origem.
         * @throws InvalidArgumentException Se a URL for inválida.
         */
        public function __construct( $url ) {
            if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
                // translators: %s: A URL inválida que foi fornecida.
                throw new InvalidArgumentException( sprintf( __( 'URL fornecida "%s" é inválida.', 'autoblogpro' ), esc_url_raw( $url ) ) );
            }
            $this->url = $url;
        }

        /**
         * Busca o conteúdo HTML da URL.
         *
         * Utiliza wp_remote_get para obter o HTML. Verifica o código de resposta
         * e armazena o corpo da resposta em $this->raw_html_content.
         * - **Logging:** Registra o início da busca, sucesso, ou falhas (erro de `wp_remote_get`,
         *   código de status HTTP inesperado, conteúdo vazio).
         *
         * @since 0.1.0 (Logging aprimorado em 0.2.0)
         * @access public
         * @return true|WP_Error True em sucesso, WP_Error em caso de falha.
         */
        public function fetch_content() {
            AutoBP_Log_Manager::info( 'Iniciando busca de conteúdo para a URL: ' . $this->url, array( 'url' => $this->url ) );
            $response = wp_remote_get( $this->url, array( 'timeout' => 30, 'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ) ) );

            if ( is_wp_error( $response ) ) {
                AutoBP_Log_Manager::error( 'Falha em wp_remote_get ao buscar URL: ' . $response->get_error_message(), array( 'url' => $this->url, 'error_code' => $response->get_error_code() ) );
                // translators: %s: Mensagem de erro detalhada do wp_remote_get.
                return new WP_Error( 'url_fetch_failed', sprintf( __( 'Falha ao buscar a URL. Erro: %s', 'autoblogpro' ), $response->get_error_message() ) );
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            if ( $response_code !== 200 ) {
                AutoBP_Log_Manager::error( "A URL retornou um código de status HTTP inesperado: {$response_code}.", array( 'url' => $this->url, 'status_code' => $response_code, 'response_body_snippet' => mb_substr(wp_remote_retrieve_body( $response ), 0, 200) ) );
                // translators: %d: Código de status HTTP retornado.
                return new WP_Error( 'url_invalid_response', sprintf( __( 'A URL retornou um código de status HTTP inesperado: %d.', 'autoblogpro' ), $response_code ) );
            }

            $this->raw_html_content = wp_remote_retrieve_body( $response );
            if ( empty( $this->raw_html_content ) ) {
                AutoBP_Log_Manager::warning( 'O conteúdo da URL está vazio após uma resposta HTTP 200.', array( 'url' => $this->url ) );
                return new WP_Error( 'url_empty_content', __( 'O conteúdo HTML da URL está vazio ou não pôde ser recuperado.', 'autoblogpro' ) );
            }
            AutoBP_Log_Manager::info( 'Conteúdo HTML bruto buscado com sucesso da URL: ' . $this->url, array( 'url' => $this->url, 'content_length' => strlen($this->raw_html_content) ) );
            return true;
        }

        /**
         * Extrai o conteúdo textual principal do HTML bruto.
         *
         * Utiliza DOMDocument para analisar o HTML. Remove tags <script> e <style>.
         * Tenta encontrar o conteúdo principal dentro de tags como <article> ou <main>.
         * Se não encontrar, usa o <body> como fallback.
         * Extrai texto de tags de parágrafo (p), cabeçalhos (h1-h6) e itens de lista (li).
         * O texto extraído é limitado em tamanho.
         * - **Logging:** Loga o início da extração, se não há HTML bruto, falhas ao carregar
         *   no DOMDocument, o seletor/tag usado para encontrar o conteúdo principal (ou fallback para body),
         *   o tamanho do texto antes e depois da limitação, e o sucesso ou falha da extração.
         *
         * @since 0.1.0 (Logging aprimorado em 0.2.0)
         * @access public
         * @return string|WP_Error O texto extraído em sucesso, ou WP_Error em falha.
         */
        public function extract_main_text() {
            AutoBP_Log_Manager::info( 'Iniciando extração de texto principal para a URL: ' . $this->url, array( 'url' => $this->url ) );

            if ( empty( $this->raw_html_content ) ) {
                AutoBP_Log_Manager::warning( 'Nenhum conteúdo HTML bruto para analisar na extração de texto.', array( 'url' => $this->url ) );
                return new WP_Error( 'no_html_to_parse', __( 'Nenhum conteúdo HTML disponível para análise. É necessário buscar o conteúdo da URL primeiro com fetch_content().', 'autoblogpro' ) );
            }

            // Define um user agent genérico para evitar problemas com alguns servidores
            // Embora o user-agent seja mais relevante para wp_remote_get, pode ser útil se DOMDocument fizer requisições (improvável aqui).
            // ini_set('user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');


            libxml_use_internal_errors(true); // Suprime erros de HTML malformado.
            $dom = new DOMDocument();
            // Converte para HTML entities e especifica UTF-8 para melhor compatibilidade com caracteres especiais.
            if ( ! $dom->loadHTML( mb_convert_encoding( $this->raw_html_content, 'HTML-ENTITIES', 'UTF-8') ) ) {
                 $xml_errors = libxml_get_errors();
                 libxml_clear_errors();
                 AutoBP_Log_Manager::error('Falha ao carregar o HTML da URL no DOMDocument.', array('url' => $this->url, 'libxml_errors' => $xml_errors));
                 return new WP_Error('html_load_failed', __('Falha ao carregar o HTML da URL no DOMDocument. O HTML pode estar severamente malformado.','autoblogpro'));
            }
            libxml_clear_errors();
            AutoBP_Log_Manager::debug( 'DOMDocument carregado para extração de texto.', array( 'url' => $this->url ) );

            // Remove scripts e styles para limpar o conteúdo.
            foreach ( iterator_to_array( $dom->getElementsByTagName('script') ) as $node) { if ($node->parentNode) { $node->parentNode->removeChild($node); } }
            foreach ( iterator_to_array( $dom->getElementsByTagName('style') ) as $node) { if ($node->parentNode) { $node->parentNode->removeChild($node); } }
            // Outros elementos comuns que não contêm conteúdo principal
            foreach ( iterator_to_array( $dom->getElementsByTagName('nav') ) as $node) { if ($node->parentNode) { $node->parentNode->removeChild($node); } }
            foreach ( iterator_to_array( $dom->getElementsByTagName('aside') ) as $node) { if ($node->parentNode) { $node->parentNode->removeChild($node); } }
            foreach ( iterator_to_array( $dom->getElementsByTagName('footer') ) as $node) { if ($node->parentNode) { $node->parentNode->removeChild($node); } }
            foreach ( iterator_to_array( $dom->getElementsByTagName('header') ) as $node) { if ($node->parentNode) { $node->parentNode->removeChild($node); } }


            $main_content_node = null;
            $found_selector = '';
            
            // Tenta por tag name primeiro
            $tag_selectors = array('article', 'main');
            foreach ($tag_selectors as $selector) {
                $nodes = $dom->getElementsByTagName($selector);
                if ($nodes->length > 0) {
                    $main_content_node = $nodes->item(0); 
                    $found_selector = $selector;
                    AutoBP_Log_Manager::debug( "Nó de conteúdo principal encontrado usando seletor/tag: {$found_selector}", array( 'url' => $this->url ) );
                    break;
                }
            }
            
            // Se não encontrou por tag, tenta por classes/IDs usando XPath (mais flexível)
            if (!$main_content_node) {
                $xpath = new DOMXPath($dom);
                $class_id_selectors = array(
                    "//*[contains(concat(' ', normalize-space(@class), ' '), ' entry-content ')]" => ".entry-content",
                    "//*[contains(concat(' ', normalize-space(@class), ' '), ' post-content ')]" => ".post-content",
                    "//*[contains(concat(' ', normalize-space(@class), ' '), ' td-post-content ')]" => ".td-post-content",
                    "//*[contains(concat(' ', normalize-space(@class), ' '), ' article-content ')]" => ".article-content",
                    "//*[contains(concat(' ', normalize-space(@class), ' '), ' content ')]" => ".content",
                    "//*[@id='content']" => "#content",
                    "//*[@id='main-content']" => "#main-content",
                    "//*[@id='main']" => "#main",
                );
                foreach ($class_id_selectors as $xpath_selector => $display_selector) {
                    $nodes = $xpath->query($xpath_selector);
                    if ($nodes && $nodes->length > 0) {
                        $main_content_node = $nodes->item(0);
                        $found_selector = $display_selector;
                        AutoBP_Log_Manager::debug( "Nó de conteúdo principal encontrado usando seletor/tag: {$found_selector}", array( 'url' => $this->url ) );
                        break;
                    }
                }
            }
            
            if (!$main_content_node) {
                AutoBP_Log_Manager::warning( 'Nenhum nó de conteúdo principal (article/main/classe específica) encontrado. Recorrendo ao body para extração.', array( 'url' => $this->url ) );
                $main_content_node = $dom->getElementsByTagName('body')->item(0);
                if (!$main_content_node) { 
                     AutoBP_Log_Manager::error( 'Falha crítica: Não foi possível encontrar o nó <body> para extração.', array('url' => $this->url) );
                     return new WP_Error( 'text_extraction_failed_no_body', __( 'Não foi possível encontrar o corpo (body) do HTML para extração.', 'autoblogpro' ) );
                }
                 $found_selector = 'body (fallback)';
            }

            $text_parts = array();
            if ($main_content_node) {
                $allowed_tags = array('p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'blockquote', 'pre');
                $iterator = new RecursiveIteratorIterator(new RecursiveDOMIterator($main_content_node), RecursiveIteratorIterator::SELF_FIRST);

                foreach ($iterator as $node) {
                    if ($node->nodeType === XML_ELEMENT_NODE && in_array(strtolower($node->nodeName), $allowed_tags)) {
                        $text_content = trim($node->textContent);
                        if (!empty($text_content)) {
                            $text_parts[] = $text_content;
                        }
                    }
                }
            }
            
            $this->extracted_text_content = implode("\n\n", array_filter($text_parts)); 
            AutoBP_Log_Manager::info( 'Texto extraído inicialmente (antes da limitação): ' . strlen($this->extracted_text_content) . ' caracteres.', array( 'url' => $this->url, 'found_by_selector' => $found_selector ) );

            if ( empty( $this->extracted_text_content ) ) {
                $error_object = new WP_Error( 'text_extraction_failed', __( 'Não foi possível extrair conteúdo textual significativo da URL. A estrutura pode ser muito diferente do esperado ou o conteúdo principal é escasso.', 'autoblogpro' ) );
                AutoBP_Log_Manager::error( 'Falha na extração de texto principal: ' . $error_object->get_error_message(), array( 'url' => $this->url, 'error_code' => $error_object->get_error_code(), 'found_by_selector' => $found_selector ) );
                return $error_object;
            }
            
            // Limita o tamanho do texto extraído para evitar sobrecarga.
            $original_length = strlen($this->extracted_text_content);
            $this->extracted_text_content = mb_substr($this->extracted_text_content, 0, 30000); 
            $final_length = strlen($this->extracted_text_content);

            if ($original_length !== $final_length) {
                AutoBP_Log_Manager::info( "Texto extraído limitado a {$final_length} caracteres (original: {$original_length}).", array( 'url' => $this->url ) );
            }
            
            AutoBP_Log_Manager::info( 'Extração de texto principal bem-sucedida para a URL: ' . $this->url, array( 'url' => $this->url, 'final_text_length' => $final_length, 'found_by_selector' => $found_selector ) );
            return $this->extracted_text_content;
        }

        /**
         * Analisa o texto fornecido usando a API OpenAI para identificar palavras-chave e tópicos.
         * - **Logging:** Registra o início da análise, se o texto ou API key estão ausentes,
         *   o prompt enviado à API (snippet), falhas na chamada `wp_remote_post`, erros da API OpenAI,
         *   sucesso na análise (com contagem de keywords/topics), ou falhas ao decodificar/parsear
         *   a resposta JSON da API.
         *
         * @since 0.1.0 (Logging aprimorado em 0.2.0)
         * @access public
         * @param string $text_to_analyze O texto a ser analisado.
         * @param string $api_key A chave da API OpenAI.
         * @return true|WP_Error True em sucesso, WP_Error em falha.
         */
        public function analyze_text_content( $text_to_analyze, $api_key ) {
            AutoBP_Log_Manager::info( 'Iniciando análise semântica para URL: ' . $this->url, array( 'url' => $this->url, 'text_snippet' => mb_substr($text_to_analyze, 0, 100) . '...' ) );

            if ( empty( $text_to_analyze ) ) {
                AutoBP_Log_Manager::warning( 'Texto para análise semântica está vazio.', array( 'url' => $this->url ) );
                return new WP_Error( 'text_for_analysis_empty', __( 'O texto fornecido para análise semântica está vazio.', 'autoblogpro' ) );
            }
            if ( empty( $api_key ) ) {
                AutoBP_Log_Manager::error( 'API Key da OpenAI ausente. Análise semântica não pode ser realizada.', array( 'url' => $this->url ) );
                return new WP_Error( 'openai_api_key_missing_for_analysis', __( 'A chave da API OpenAI é necessária para a análise semântica.', 'autoblogpro' ) );
            }

            // Limitar o texto enviado para a API para evitar exceder limites de token/custo.
            // Ex: primeiros 15000 caracteres (aproximadamente 3k-4k tokens)
            $text_for_api = mb_substr( $text_to_analyze, 0, 15000 );

            $prompt_lines = array();
            $prompt_lines[] = __( 'Analise o seguinte texto e forneça:', 'autoblogpro' );
            $prompt_lines[] = __( '1. Uma lista das 5-10 palavras-chave mais relevantes e específicas (evite palavras genéricas).', 'autoblogpro' );
            $prompt_lines[] = __( '2. Uma lista dos 3-5 principais tópicos ou temas abordados no texto.', 'autoblogpro' );
            $prompt_lines[] = __( 'Formate a resposta EXCLUSIVAMENTE como um objeto JSON com duas chaves: "keywords" (um array de strings) e "topics" (um array de strings). Não inclua nenhuma explicação ou texto adicional fora do objeto JSON.', 'autoblogpro' );
            $prompt_lines[] = __( 'Texto para análise:', 'autoblogpro' );
            $prompt_lines[] = $text_for_api;
            
            $full_prompt = implode( "\n", $prompt_lines );
            AutoBP_Log_Manager::debug( 'Prompt para análise semântica OpenAI:', array( 'url' => $this->url, 'prompt_length' => strlen($full_prompt), 'prompt_snippet' => mb_substr($full_prompt, 0, 300) . "..." ) );

            $api_url = 'https://api.openai.com/v1/chat/completions';
            $body = array(
                'model'    => 'gpt-3.5-turbo',
                'messages' => array(
                    // translators: Instrução para a IA sobre seu papel na análise de texto.
                    array('role' => 'system', 'content' => __( 'Você é um assistente de análise de texto altamente preciso, especializado em identificar palavras-chave e tópicos principais de um artigo. Sua resposta deve ser APENAS o objeto JSON solicitado.', 'autoblogpro' ) ),
                    array('role' => 'user', 'content' => $full_prompt )
                ),
                'temperature' => 0.2, // Baixa temperatura para respostas mais factuais/determinísticas
                'max_tokens'  => 350, // Ajustado para acomodar uma lista razoável de palavras-chave e tópicos
            );

            $args = array(
                'body'    => wp_json_encode( $body ),
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'timeout' => 60,
            );

            $response = wp_remote_post( $api_url, $args );

            if ( is_wp_error( $response ) ) {
                AutoBP_Log_Manager::error( 'Falha na chamada wp_remote_post para análise semântica: ' . $response->get_error_message(), array( 'url' => $this->url, 'error_code' => $response->get_error_code() ) );
                return new WP_Error( 'analysis_api_request_failed', $response->get_error_message() );
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response ); // Salvar para log
            $decoded_response = json_decode( $response_body, true );

            if ( isset($decoded_response['usage']['total_tokens']) && class_exists('AutoBlogPro') ) {
                AutoBlogPro::add_to_openai_token_usage( (int) $decoded_response['usage']['total_tokens'] );
            }

            if ( $response_code !== 200 ) {
                $error_message = isset( $decoded_response['error']['message'] ) ? $decoded_response['error']['message'] : __( 'Erro desconhecido da API OpenAI durante a análise.', 'autoblogpro' );
                AutoBP_Log_Manager::error( "API OpenAI retornou erro {$response_code} para análise semântica: " . $error_message, array( 'url' => $this->url, 'status_code' => $response_code, 'response_body_snippet' => mb_substr($response_body, 0, 200) ) );
                return new WP_Error( 'analysis_api_error', $error_message );
            }

            if ( isset( $decoded_response['choices'][0]['message']['content'] ) ) {
                $openai_response_content = $decoded_response['choices'][0]['message']['content'];
                // Tentar extrair JSON do conteúdo, pois a IA pode adicionar ```json ... ```
                if (preg_match('/```json\s*([\s\S]*?)\s*```/', $openai_response_content, $matches)) {
                    $json_part = $matches[1];
                } else {
                    $json_part = $openai_response_content;
                }

                $analyzed_data = json_decode( trim($json_part), true );

                if ( json_last_error() === JSON_ERROR_NONE && isset( $analyzed_data['keywords'] ) && isset( $analyzed_data['topics'] ) ) {
                    $this->identified_keywords = array_map('sanitize_text_field', (array) $analyzed_data['keywords'] );
                    $this->identified_topics = array_map('sanitize_text_field', (array) $analyzed_data['topics'] );
                    AutoBP_Log_Manager::info( 'Análise semântica concluída com sucesso.', array( 'url' => $this->url, 'keywords_found' => count($this->identified_keywords), 'topics_found' => count($this->identified_topics) ) );
                    AutoBP_Log_Manager::debug( 'Dados da análise semântica:', array( 'url' => $this->url, 'keywords' => $this->identified_keywords, 'topics' => $this->identified_topics ) );
                    return true;
                } else {
                    $error_msg = 'Falha ao decodificar JSON da resposta da análise semântica ou formato inesperado.';
                    AutoBP_Log_Manager::warning( $error_msg, array( 'url' => $this->url, 'response_body_snippet' => mb_substr($openai_response_content, 0, 200), 'json_last_error' => json_last_error_msg() ) );
                    return new WP_Error( 'analysis_parsing_failed', __( 'Falha ao analisar a estrutura JSON da resposta da análise semântica da IA.', 'autoblogpro' ) );
                }
            } else {
                $error_msg = 'Resposta inesperada da API OpenAI durante a análise (sem campo de conteúdo principal).';
                AutoBP_Log_Manager::warning( $error_msg, array( 'url' => $this->url, 'decoded_response_keys' => array_keys($decoded_response) ) );
                return new WP_Error( 'analysis_unexpected_response', __( 'Resposta inesperada da API OpenAI durante a análise (sem conteúdo).', 'autoblogpro' ) );
            }
        }

        /**
         * Retorna as palavras-chave identificadas.
         * @since 0.1.0
         * @return array
         */
        public function get_identified_keywords() {
            return $this->identified_keywords;
        }

        /**
         * Retorna os tópicos identificados.
         * @since 0.1.0
         * @return array
         */
        public function get_identified_topics() {
            return $this->identified_topics;
        }

        /**
         * Retorna os insights adicionais identificados.
         * @since 0.1.0
         * @return array
         */
        public function get_additional_insights() {
            return $this->additional_insights;
        }

        /**
         * Busca insights adicionais com base nas palavras-chave, tópicos e um trecho do texto original.
         * 
         * Este método envia uma requisição para a API OpenAI para gerar ideias, fatos
         * ou pontos de discussão que podem ser usados para enriquecer o conteúdo.
         *
         * @since 0.1.0
         * @access public
         * @param array  $keywords Palavras-chave identificadas.
         * @param array  $topics Tópicos principais identificados.
         * @param string $original_text_snippet Um trecho do texto original.
         * @param string $api_key Chave da API OpenAI.
         * @return true|WP_Error True em sucesso (insights armazenados na propriedade da classe), WP_Error em falha.
         * - **Logging:** Loga o início da busca, se API key/keywords/topics/snippet estão ausentes,
         *   o prompt enviado (snippet), falhas na chamada `wp_remote_post`, erros da API OpenAI,
         *   sucesso (com contagem de insights), ou respostas inesperadas da API.
         */
        public function fetch_additional_insights( $keywords, $topics, $original_text_snippet, $api_key ) {
            AutoBP_Log_Manager::info( 'Iniciando busca por insights adicionais para URL: ' . $this->url, array( 'url' => $this->url, 'num_keywords' => count($keywords), 'num_topics' => count($topics), 'snippet_length' => mb_strlen($original_text_snippet) ) );

            if ( empty( $api_key ) ) {
                AutoBP_Log_Manager::error( 'API Key da OpenAI ausente. Busca por insights adicionais não pode ser realizada.', array( 'url' => $this->url ) );
                return new WP_Error( 'openai_api_key_missing_for_insights', __( 'A chave da API OpenAI é necessária para buscar insights adicionais.', 'autoblogpro' ) );
            }
            if ( empty( $keywords ) && empty( $topics ) ) {
                 AutoBP_Log_Manager::warning( 'Palavras-chave e tópicos estão vazios. Não há base suficiente para buscar insights adicionais.', array( 'url' => $this->url ) );
                return new WP_Error( 'no_basis_for_insights', __( 'Palavras-chave e tópicos estão vazios; não há base para buscar insights adicionais.', 'autoblogpro' ) );
            }
            if ( empty( $original_text_snippet ) ) {
                 AutoBP_Log_Manager::warning( 'Trecho do texto original ausente para busca de insights.', array( 'url' => $this->url ) );
                 return new WP_Error( 'original_text_snippet_missing_for_insights', __( 'Um trecho do texto original é necessário para buscar insights adicionais.', 'autoblogpro' ) );
            }

            $keywords_str = implode(', ', $keywords);
            $topics_str = implode(', ', $topics);
            $text_snippet_for_prompt = mb_substr( $original_text_snippet, 0, 1000 ); // Limitar o snippet para o prompt

            $prompt_lines = array();
            $prompt_lines[] = __( 'Com base nas seguintes palavras-chave, tópicos principais e trecho de um artigo, sugira 3-5 insights adicionais, fatos interessantes ou pontos de discussão que poderiam enriquecer ou expandir o conteúdo sobre este assunto. Forneça cada insight em uma nova linha, como uma frase curta e acionável.', 'autoblogpro');
            $prompt_lines[] = sprintf( __('Palavras-chave: %s', 'autoblogpro'), $keywords_str );
            $prompt_lines[] = sprintf( __('Tópicos Principais: %s', 'autoblogpro'), $topics_str );
            $prompt_lines[] = __( 'Trecho do Artigo Original (para contexto):', 'autoblogpro');
            $prompt_lines[] = $text_snippet_for_prompt;
            $prompt_lines[] = __( 'Insights Sugeridos (um por linha):', 'autoblogpro');
            
            $full_prompt = implode("\n", $prompt_lines);
            AutoBP_Log_Manager::debug( 'Prompt para busca de insights adicionais OpenAI:', array( 'url' => $this->url, 'prompt_length' => strlen($full_prompt), 'prompt_snippet' => mb_substr($full_prompt, 0, 300) . "..." ) );

            $api_url = 'https://api.openai.com/v1/chat/completions';
            $body = array(
                'model'    => 'gpt-3.5-turbo',
                'messages' => array(
                    // translators: Instrução para a IA sobre seu papel.
                    array('role' => 'system', 'content' => __( 'Você é um assistente de pesquisa criativo, focado em identificar informações valiosas e pontos de expansão para artigos.', 'autoblogpro' ) ),
                    array('role' => 'user', 'content' => $full_prompt )
                ),
                'temperature' => 0.6, 
                'max_tokens'  => 200, // Ajustado para 3-5 insights curtos
            );

            $args = array(
                'body'    => wp_json_encode( $body ),
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'timeout' => 60, 
            );

            $response = wp_remote_post( $api_url, $args );

            if ( is_wp_error( $response ) ) {
                AutoBP_Log_Manager::error( 'Falha na chamada wp_remote_post para busca de insights: ' . $response->get_error_message(), array( 'url' => $this->url, 'error_code' => $response->get_error_code() ) );
                return new WP_Error( 'insights_api_request_failed', $response->get_error_message() );
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response ); 
            $decoded_response = json_decode( $response_body, true );

            if ( isset($decoded_response['usage']['total_tokens']) && class_exists('AutoBlogPro') ) {
                AutoBlogPro::add_to_openai_token_usage( (int) $decoded_response['usage']['total_tokens'] );
            }

            if ( $response_code !== 200 ) {
                $error_message = isset( $decoded_response['error']['message'] ) ? $decoded_response['error']['message'] : __( 'Erro desconhecido da API OpenAI durante a busca por insights.', 'autoblogpro' );
                AutoBP_Log_Manager::error( "API OpenAI retornou erro {$response_code} para busca de insights: " . $error_message, array( 'url' => $this->url, 'status_code' => $response_code, 'response_body_snippet' => mb_substr($response_body, 0, 200) ) );
                return new WP_Error( 'insights_api_error', $error_message );
            }

            if ( isset( $decoded_response['choices'][0]['message']['content'] ) ) {
                $insights_text = trim( $decoded_response['choices'][0]['message']['content'] );
                $this->additional_insights = array_map('sanitize_text_field', array_filter(explode("\n", $insights_text)));
                AutoBP_Log_Manager::info( 'Busca por insights adicionais concluída com sucesso.', array( 'url' => $this->url, 'insights_found' => count($this->additional_insights) ) );
                AutoBP_Log_Manager::debug( 'Insights adicionais encontrados:', array( 'url' => $this->url, 'insights' => $this->additional_insights ) );
                return true;
            } else {
                $error_msg = 'Resposta inesperada da API OpenAI durante a busca por insights (sem campo de conteúdo principal).';
                AutoBP_Log_Manager::warning( $error_msg, array( 'url' => $this->url, 'decoded_response_keys' => array_keys($decoded_response), 'response_body_snippet' => mb_substr($response_body, 0, 200) ) );
                return new WP_Error( 'insights_unexpected_response', __( 'Resposta inesperada da API OpenAI durante a busca por insights (sem conteúdo).', 'autoblogpro' ) );
            }
        }
        
        /**
         * Reescreve e expande o texto fornecido usando a API OpenAI.
         *
         * @since 0.1.0
         * @access public
         * @param string $original_text O texto original extraído da URL.
         * @param array  $keywords Palavras-chave identificadas para focar.
         * @param array  $topics Tópicos principais identificados.
         * @param array  $insights Insights adicionais para expandir o conteúdo.
         * @param string $api_key Chave da API OpenAI.
         * @param string $target_language Idioma alvo para o novo artigo (ex: 'pt-BR').
         * @param int    $num_h2_sections Número desejado de seções H2.
         * @param string $tone_of_voice Tom de voz para o novo artigo.
         * @param string $writing_style Estilo de escrita para o novo artigo.
         * @param float  $creativity Nível de criatividade (temperatura).
         * @param int    $article_length Tamanho aproximado desejado para o artigo reescrito.
         * @return array|WP_Error Array com 'title' e 'content' em sucesso, WP_Error em falha.
         * - **Logging:** Registra o início da reescrita, se API key/texto original estão ausentes,
         *   o prompt enviado (snippet), falhas na chamada `wp_remote_post`, erros da API OpenAI,
         *   sucesso na extração de título/conteúdo da resposta, ou respostas vazias/inesperadas da API.
         */
        public function rewrite_and_expand_content( $original_text, $keywords, $topics, $insights, $api_key, $target_language = 'pt-BR', $num_h2_sections = 4, $tone_of_voice = 'informativo', $writing_style = 'neutro', $creativity = 0.7, $article_length = 800 ) {
            AutoBP_Log_Manager::info( 'Iniciando reescrita/expansão de conteúdo para URL: ' . $this->url, 
                array( 
                    'url' => $this->url, 
                    'num_keywords' => count($keywords), 
                    'num_topics' => count($topics), 
                    'num_insights' => count($insights),
                    'target_language' => $target_language,
                    'article_length_target' => $article_length
                ) 
            );
            
            if ( empty( $api_key ) ) {
                AutoBP_Log_Manager::error( 'API Key da OpenAI ausente. Reescrita/expansão não pode ser realizada.', array( 'url' => $this->url ) );
                return new WP_Error( 'openai_api_key_missing_for_rewrite', __( 'A chave da API OpenAI é necessária para reescrever e expandir o conteúdo.', 'autoblogpro' ) );
            }
            if ( empty( $original_text ) ) {
                AutoBP_Log_Manager::warning( 'Texto original ausente para reescrita/expansão.', array( 'url' => $this->url ) );
                return new WP_Error( 'original_text_missing_for_rewrite', __( 'O texto original é necessário para o processo de reescrita.', 'autoblogpro' ) );
            }

            // Preparar strings de arrays para o prompt
            $keywords_string = !empty($keywords) ? implode(', ', $keywords) : __('não especificadas', 'autoblogpro');
            $topics_string = !empty($topics) ? implode(', ', $topics) : __('não especificados', 'autoblogpro');
            $insights_string = !empty($insights) ? "- " . implode("\n- ", $insights) : __('nenhum insight adicional fornecido', 'autoblogpro');

            // Limitar o tamanho do texto original enviado no prompt para evitar exceder limites de token.
            $original_text_snippet_for_prompt = mb_substr( $original_text, 0, 10000 ); // Ex: primeiros 10k caracteres.

            $prompt_lines = array();
            // translators: %s: Idioma alvo.
            $prompt_lines[] = sprintf( __("Sua tarefa é criar um novo artigo de blog abrangente e original no idioma '%s'.", 'autoblogpro'), $target_language );
            $prompt_lines[] = __( 'O novo artigo deve ser baseado no seguinte texto original, mas **reescrito significativamente** para garantir alta originalidade:', 'autoblogpro');
            $prompt_lines[] = "--- TEXTO ORIGINAL (para referência) ---";
            $prompt_lines[] = $original_text_snippet_for_prompt;
            $prompt_lines[] = "--- FIM DO TEXTO ORIGINAL ---";
            $prompt_lines[] = "";
            $prompt_lines[] = __( 'Ao criar o novo artigo, siga estas instruções cruciais:', 'autoblogpro');
            $prompt_lines[] = __( '1. **Originalidade Total:** Reescreva completamente o texto original. Não copie frases ou parágrafos diretamente. O objetivo é um conteúdo novo e único.', 'autoblogpro');
            // translators: %s: Palavras-chave a serem incorporadas.
            $prompt_lines[] = sprintf( __("2. **Palavras-chave:** Incorpore naturalmente as seguintes palavras-chave no novo texto: [%s].", 'autoblogpro'), $keywords_string );
            // translators: %s: Tópicos principais a serem expandidos.
            $prompt_lines[] = sprintf( __("3. **Expansão de Tópicos e Insights:** Expanda e aprofunde os seguintes tópicos principais: [%s]. Utilize os seguintes insights e informações adicionais para enriquecer o conteúdo:", 'autoblogpro'), $topics_string );
            $prompt_lines[] = "   --- INSIGHTS ADICIONAIS (use-os para adicionar valor) ---";
            $prompt_lines[] = "   " . $insights_string;
            $prompt_lines[] = "   --- FIM DOS INSIGHTS ADICIONAIS ---";
            // translators: %d: Número estimado de palavras para o novo artigo.
            $prompt_lines[] = sprintf( __("4. **Tamanho:** O artigo final deve ter aproximadamente %d palavras.", 'autoblogpro'), $article_length );
            // translators: %d: Número de seções H2.
            $prompt_lines[] = sprintf( __("5. **Estrutura:** Estruture o artigo em %d seções principais. Cada seção principal deve ser claramente introduzida por um subtítulo H2 (usando a formatação markdown '## Título da Seção').", 'autoblogpro'), $num_h2_sections );
             // translators: %s: Tom de voz. %s: Estilo de escrita.
            $prompt_lines[] = sprintf( __("6. **Tom e Estilo:** Adote um tom de voz %s e um estilo de escrita %s.", 'autoblogpro'), $tone_of_voice, $writing_style );
            $prompt_lines[] = __( '7. **Coesão e Qualidade:** Certifique-se de que o artigo final seja coeso, bem escrito, gramaticalmente correto e pronto para publicação.', 'autoblogpro');
            $prompt_lines[] = __( '8. **Formato da Saída:** Gere apenas o conteúdo do novo artigo. Comece DIRETAMENTE com o título do novo artigo em uma linha separada no início, seguido pelo corpo do artigo. Não inclua introduções como "Aqui está o artigo:" ou qualquer texto antes do título.', 'autoblogpro');

            $full_prompt = implode( "\n", $prompt_lines );
            AutoBP_Log_Manager::debug( 'Prompt para reescrita/expansão OpenAI (início): ' . mb_substr($full_prompt, 0, 500) . '...', array( 'url' => $this->url, 'prompt_length' => strlen($full_prompt) ) );

            $api_url = 'https://api.openai.com/v1/chat/completions';
            $body = array(
                'model'    => 'gpt-3.5-turbo', // ou gpt-4 se disponível e preferido
                'messages' => array(
                    // translators: Instrução para a IA sobre seu papel como redator de blog.
                    array('role' => 'system', 'content' => __( 'Você é um redator de blog especialista, proficiente em reescrever e expandir conteúdo de forma original e otimizada para SEO. Sua resposta deve ser APENAS o título e o corpo do novo artigo.', 'autoblogpro' ) ),
                    array('role' => 'user', 'content' => $full_prompt )
                ),
                'temperature' => floatval($creativity), 
                'max_tokens'  => intval($article_length * 2.0), // Ajuste generoso para permitir a expansão
            );

            $args = array(
                'body'    => wp_json_encode( $body ),
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'timeout' => 180, // Timeout maior para tarefas de geração mais longas
            );

            $response = wp_remote_post( $api_url, $args );

            if ( is_wp_error( $response ) ) {
                AutoBP_Log_Manager::error( 'Falha na chamada wp_remote_post para reescrita/expansão: ' . $response->get_error_message(), array( 'url' => $this->url, 'error_code' => $response->get_error_code() ) );
                return new WP_Error( 'rewrite_api_request_failed', $response->get_error_message() );
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response ); // Guardar para log
            $decoded_response = json_decode( $response_body, true );

            if ( isset($decoded_response['usage']['total_tokens']) && class_exists('AutoBlogPro') ) {
                AutoBlogPro::add_to_openai_token_usage( (int) $decoded_response['usage']['total_tokens'] );
            }

            if ( $response_code !== 200 ) {
                $error_message = isset( $decoded_response['error']['message'] ) ? $decoded_response['error']['message'] : __( 'Erro desconhecido da API OpenAI durante a reescrita.', 'autoblogpro' );
                AutoBP_Log_Manager::error( "API OpenAI retornou erro {$response_code} para reescrita/expansão: " . $error_message, array( 'url' => $this->url, 'status_code' => $response_code, 'response_body_snippet' => mb_substr($response_body, 0, 200) ) );
                return new WP_Error( 'rewrite_api_error', $error_message );
            }

            if ( isset( $decoded_response['choices'][0]['message']['content'] ) ) {
                $generated_text = trim( $decoded_response['choices'][0]['message']['content'] );
                
                // Tentar separar título e conteúdo
                $text_lines = explode("\n", $generated_text, 2);
                $generated_title = trim($text_lines[0]);
                // Remover formatação markdown do título se presente (ex: ## Título)
                $generated_title = preg_replace('/^#+\s*/', '', $generated_title);

                $generated_content = isset($text_lines[1]) ? trim($text_lines[1]) : '';

                if ( empty($generated_title) && empty($generated_content) ) {
                     $error_obj = new WP_Error( 'rewrite_empty_response', __( 'A API retornou uma resposta vazia para o título e conteúdo.', 'autoblogpro' ) );
                     AutoBP_Log_Manager::error( 'Falha na reescrita/expansão: ' . $error_obj->get_error_message(), array( 'url' => $this->url, 'error_code' => $error_obj->get_error_code(), 'response_body_snippet' => mb_substr($generated_text, 0, 200) ) );
                     return $error_obj;
                }
                if ( empty($generated_title) && !empty($generated_content) ) {
                    // Se não houver título, usar um placeholder ou tentar extrair do conteúdo
                    $generated_title = mb_substr(strip_tags($generated_content), 0, 60) . '...';
                    if(empty($generated_title)) $generated_title = __("Artigo Reescrito (título pendente)", "autoblogpro");
                    AutoBP_Log_Manager::warning( 'Título não foi gerado explicitamente pela IA para reescrita/expansão, usando snippet do conteúdo como fallback.', array('url' => $this->url, 'fallback_title' => $generated_title) );
                }
                 if ( empty($generated_content) && !empty($generated_title) ) {
                    $error_obj = new WP_Error( 'rewrite_empty_content_response', __( 'A API retornou um título mas o conteúdo do artigo está vazio.', 'autoblogpro' ) );
                    AutoBP_Log_Manager::error( 'Falha na reescrita/expansão: ' . $error_obj->get_error_message(), array( 'url' => $this->url, 'error_code' => $error_obj->get_error_code(), 'generated_title' => $generated_title ) );
                    return $error_obj;
                }

                AutoBP_Log_Manager::info( 'Título e conteúdo extraídos da resposta da IA para reescrita.', array( 'url' => $this->url, 'generated_title' => $generated_title, 'content_snippet' => mb_substr($generated_content, 0, 100) . '...' ) );
                return array('title' => $generated_title, 'content' => $generated_content);
            } else {
                $error_msg = 'Resposta inesperada da API OpenAI durante a reescrita (sem campo de conteúdo principal).';
                AutoBP_Log_Manager::warning( $error_msg, array( 'url' => $this->url, 'decoded_response_keys' => array_keys($decoded_response), 'response_body_snippet' => mb_substr($response_body, 0, 200) ) );
                return new WP_Error( 'rewrite_unexpected_response', __( 'Resposta inesperada da API OpenAI durante a reescrita (sem conteúdo).', 'autoblogpro' ) );
            }
        }
    }
}

// Helper para iterar sobre nós do DOM recursivamente (se não existir em outro lugar)
if (!class_exists('RecursiveDOMIterator')) {
    class RecursiveDOMIterator extends RecursiveArrayIterator implements RecursiveIterator {
        public function __construct($nodeOrNodeList) {
            if ($nodeOrNodeList instanceof DOMNodeList) {
                parent::__construct(iterator_to_array($nodeOrNodeList));
            } elseif ($nodeOrNodeList instanceof DOMNode) {
                 // Apenas iterar sobre elementos filhos se for um nó de elemento.
                if ($nodeOrNodeList->nodeType === XML_ELEMENT_NODE) {
                    parent::__construct(iterator_to_array($nodeOrNodeList->childNodes));
                } else {
                    parent::__construct(array());
                }
            } else {
                 parent::__construct(array());
            }
        }

        public function hasChildren() : bool {
            $current = $this->current();
            return $current instanceof DOMNode && $current->nodeType === XML_ELEMENT_NODE && $current->hasChildNodes();
        }

        public function getChildren() : ?RecursiveDOMIterator {
             $current = $this->current();
             if ($current instanceof DOMNode && $current->nodeType === XML_ELEMENT_NODE && $current->hasChildNodes()) {
                return new self($current->childNodes);
            }
            return null; 
        }
    }
}

?>
