<?php
/**
 * Classe AutoBP_Content_Generator.
 *
 * Responsável pela lógica de geração de conteúdo utilizando a API OpenAI.
 * Constrói prompts detalhados com base nos parâmetros fornecidos,
 * interage com a API e salva o conteúdo gerado como um rascunho de post no WordPress,
 * incluindo título, conteúdo, categorias (herdadas do nicho) e metadados relevantes.
 *
 * @package     AutoBlogPro
 * @subpackage  Includes
 * @since       0.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AutoBP_Content_Generator' ) ) {
    /**
     * Classe AutoBP_Content_Generator.
     * 
     * Orquestra a validação de dados, construção de prompts, comunicação com
     * a API OpenAI e criação de posts no WordPress.
     */
    class AutoBP_Content_Generator {

        /**
         * Chave da API OpenAI.
         * @since 0.1.0
         * @access private
         * @var string
         */
        private $api_key;

        /**
         * ID do post do tipo 'niche' selecionado, do qual o artigo será gerado.
         * @since 0.1.0
         * @access private
         * @var int
         */
        private $niche_id;

        /**
         * Número de artigos a serem gerados (atualmente, a classe gera 1 por chamada a `generate()`).
         * @since 0.1.0
         * @access private
         * @var int
         */
        private $num_articles;

        /**
         * Palavras-chave alvo principais para o conteúdo do artigo.
         * @since 0.1.0
         * @access private
         * @var string
         */
        private $target_keywords;

        /**
         * Tamanho médio estimado do artigo, em palavras. Usado para instruir a IA e estimar `max_tokens`.
         * @since 0.1.0
         * @access private
         * @var int
         */
        private $article_length;

        /**
         * Tom de voz desejado para o artigo (ex: 'formal', 'informal', 'criativo').
         * @since 0.1.0
         * @access private
         * @var string
         */
        private $tone_of_voice;

        /**
         * Estilo de escrita desejado para o artigo (ex: 'informativo', 'narrativo', 'tutorial').
         * @since 0.1.0
         * @access private
         * @var string
         */
        private $writing_style;

        /**
         * Nível de criatividade para a API OpenAI, corresponde ao parâmetro `temperature`.
         * @since 0.1.0
         * @access private
         * @var float
         */
        private $creativity;

        /**
         * Palavras-chave ou tópicos a serem explicitamente evitados pela IA no artigo.
         * @since 0.1.0
         * @access private
         * @var string
         */
        private $negative_keywords;

        /**
         * Idioma de geração do artigo (ex: 'pt-BR', 'en-US').
         * @since 0.1.0
         * @access private
         * @var string
         */
        private $language;

        /**
         * Número desejado de seções H2 no artigo.
         * @since 0.1.0
         * @access private
         * @var int
         */
        private $num_h2_sections;

        /**
         * Se deve gerar automaticamente a meta descrição.
         * @since 0.2.0
         * @access private
         * @var bool
         */
        private $generate_meta_desc;
        

        /**
         * Construtor da classe AutoBP_Content_Generator.
         *
         * Inicializa todas as propriedades necessárias para a geração de conteúdo,
         * recebidas do formulário de geração de artigos.
         *
         * @since 0.1.0
         * @access public
         * @param string $api_key Chave da API OpenAI.
         * @param int    $niche_id ID do CPT 'niche' selecionado.
         * @param int    $num_articles Número de artigos a gerar (atualmente processa 1 por vez).
         * @param string $target_keywords Palavras-chave principais para o artigo.
         * @param int    $article_length Tamanho estimado do artigo (em palavras).
         * @param string $tone_of_voice Tom de voz para o artigo (ex: 'formal', 'informal').
         * @param string $writing_style Estilo de escrita (ex: 'informativo', 'narrativo').
         * @param float  $creativity Nível de criatividade (temperatura da API, 0.0 a 2.0).
         * @param string $negative_keywords Palavras-chave a serem evitadas.
         * @param string $language Idioma para a geração do conteúdo (ex: 'pt-BR').
         * @param int    $num_h2_sections Número desejado de seções H2.
         * @param bool   $generate_meta_desc Se deve gerar a meta descrição. (Desde 0.2.0)
         */
        public function __construct( $api_key, $niche_id, $num_articles, $target_keywords, $article_length, $tone_of_voice, $writing_style, $creativity, $negative_keywords, $language, $num_h2_sections, $generate_meta_desc = true ) {
            $this->api_key = $api_key;
            $this->niche_id = $niche_id;
            $this->num_articles = $num_articles; 
            $this->target_keywords = $target_keywords;
            $this->article_length = $article_length;
            $this->tone_of_voice = $tone_of_voice;
            $this->writing_style = $writing_style;
            $this->creativity = $creativity;
            $this->negative_keywords = $negative_keywords;
            $this->language = $language;
            $this->num_h2_sections = $num_h2_sections;
            $this->generate_meta_desc = (bool) $generate_meta_desc;

            // Validação inicial dos parâmetros pode ser feita aqui, se desejado,
            // ou deixar para o método generate() lidar com isso de forma mais completa.
        }

        /**
         * Retorna um array com os parâmetros de geração para logging.
         * Exclui a chave da API por segurança.
         * 
         * @since 0.2.0
         * @access private
         * @return array Parâmetros de geração.
         */
        private function get_all_params_for_log() {
            return array(
                'niche_id' => $this->niche_id,
                'num_articles' => $this->num_articles,
                'target_keywords' => $this->target_keywords,
                'article_length' => $this->article_length,
                'tone_of_voice' => $this->tone_of_voice,
                'writing_style' => $this->writing_style,
                'creativity' => $this->creativity,
                'negative_keywords' => $this->negative_keywords,
                'language' => $this->language,
                'num_h2_sections' => $this->num_h2_sections,
                'generate_meta_desc' => $this->generate_meta_desc,
                // Não incluir $this->api_key diretamente no log por segurança
                'api_key_set' => !empty($this->api_key) ? 'true' : 'false',
            );
        }

        /**
         * Gera um único artigo utilizando a API OpenAI com base nos parâmetros fornecidos.
         *
         * O método realiza as seguintes etapas:
         * 1. Valida os parâmetros de entrada (API key, nicho, palavras-chave, etc.).
         * 2. Constrói um prompt de sistema e um prompt de usuário detalhados para a API OpenAI,
         *    incorporando idioma, tamanho desejado, tom, estilo e palavras-chave (alvo e negativas).
         * 3. Realiza uma chamada POST para o endpoint `v1/chat/completions` da API OpenAI
         *    usando `wp_remote_post()`. Os parâmetros `temperature` (derivado de `$this->creativity`)
         *    e `max_tokens` (estimado de `$this->article_length`) são configurados.
         * 4. Processa a resposta da API:
         *    - Verifica erros na comunicação HTTP (`wp_remote_post` errors).
         *    - Verifica o código de status HTTP da resposta da API.
         *    - Decodifica a resposta JSON.
         *    - Trata erros específicos retornados pela API OpenAI (ex: autenticação, cota, etc.).
         *    - Extrai o conteúdo textual gerado de `choices[0]['message']['content']`.
         * 5. Se o conteúdo for gerado com sucesso:
         *    - Cria um título para o post baseado nas palavras-chave alvo.
         *    - Prepara os dados do post (`post_title`, `post_content`, `post_status`='draft', `post_author`).
         *    - Insere o novo post no WordPress usando `wp_insert_post()`.
         *    - Se o post for inserido com sucesso, associa o ID do nicho de origem ao post
         *      como um metadado (`_autobp_generated_from_niche_id`) usando `update_post_meta()`.
         *    - Associa as categorias do nicho ao novo post usando `wp_set_post_categories()`.
         * 6. Retorna o ID do novo post em caso de sucesso, ou um objeto `WP_Error` se ocorrer
         *    qualquer falha durante o processo, com mensagens de erro detalhadas e traduzíveis.
         *    Posteriormente, se a opção estiver habilitada, chama `_generate_and_save_meta_description()`
         *    para gerar e salvar a meta descrição do post.
         *    Também chama `AutoBlogPro::find_internal_link_suggestions()` para encontrar e salvar
         *    sugestões de links internos.
         *    Finalmente, chama `_generate_and_save_title_suggestions()` para gerar e salvar
         *    sugestões de títulos otimizados para SEO. (Desde 0.2.0)
         *
         * @since 0.1.0 (Meta descrição, sugestões de link e sugestões de título adicionadas em 0.2.0)
         * @access public
         * @param int $article_index O índice do artigo atual no lote de geração (1-indexado).
         * @return int|WP_Error O ID do post gerado em caso de sucesso, ou `WP_Error` em caso de falha.
         */
        public function generate( $article_index = 1 ) {
            AutoBP_Log_Manager::info( 'Iniciando geração de artigo (Modo Automático).', array( 'params' => $this->get_all_params_for_log(), 'article_index' => $article_index ) );

            // Validação robusta dos parâmetros de entrada.
            if ( empty( $this->api_key ) ) {
                AutoBP_Log_Manager::error( 'Geração de artigo falhou: API Key não definida.', $this->get_all_params_for_log() );
                return new WP_Error( 'api_key_missing', __( 'Configuração pendente: A chave da API OpenAI não foi definida. Por favor, adicione-a na página de configurações do AutoBlogPro.', 'autoblogpro' ) );
            }
            if ( empty( $this->niche_id ) || !is_numeric($this->niche_id) || $this->niche_id <= 0 ) {
                return new WP_Error( 'niche_invalid', __( 'Seleção inválida: Nenhum nicho válido foi selecionado para a geração do artigo. Por favor, selecione um nicho na lista.', 'autoblogpro' ) );
            }
            if ( empty( $this->target_keywords ) ) {
                return new WP_Error( 'keywords_missing', __( 'Dados incompletos: As palavras-chave alvo são obrigatórias para a geração do artigo. Por favor, forneça as palavras-chave.', 'autoblogpro' ) );
            }
             if ( !is_numeric($this->article_length) || $this->article_length < 50 ) { 
                return new WP_Error( 'article_length_invalid', __( 'Parâmetro inválido: O tamanho estimado do artigo deve ser um valor numérico de pelo menos 50 palavras.', 'autoblogpro' ) );
            }
            // A API OpenAI para 'temperature' (que estamos chamando de 'creativity' no formulário) aceita valores de 0 a 2.0.
            // O formulário está limitado a 0.1-1.0, o que é uma faixa segura e comum para este parâmetro.
            if ( !is_numeric($this->creativity) || $this->creativity < 0.0 || $this->creativity > 2.0 ) { 
                 return new WP_Error( 'creativity_invalid_range', __( 'Parâmetro inválido: O nível de criatividade está fora da faixa permitida (0.0 - 2.0). Verifique o valor fornecido.', 'autoblogpro' ) );
            }
            if ( empty( $this->language ) ) {
                return new WP_Error( 'language_missing', __( 'Dados incompletos: O idioma de geração do artigo é obrigatório. Por favor, especifique o idioma.', 'autoblogpro' ) );
            }
            if ( ! is_numeric( $this->num_h2_sections ) || $this->num_h2_sections < 1 || $this->num_h2_sections > 10 ) {
                return new WP_Error( 'num_h2_sections_invalid', __( 'Parâmetro inválido: O número de seções H2 deve ser um valor numérico entre 1 e 10.', 'autoblogpro' ) );
            }
            // $this->num_articles não é usado diretamente aqui, pois geramos um artigo por vez.
            // O loop para múltiplos artigos será gerenciado externamente, se necessário.

            $api_url = 'https://api.openai.com/v1/chat/completions';

            // Construção do Prompt do Sistema
            // translators: %s: Idioma do artigo (ex: Português (Brasil), Inglês (EUA)).
            $system_prompt = sprintf(
                __('Você é um assistente de blog especialista, fluente em %s. Sua tarefa é escrever artigos de alta qualidade, seguindo precisamente as instruções fornecidas sobre tom, estilo e conteúdo.', 'autoblogpro'),
                $this->language // Ex: "pt-BR" ou o valor fornecido pelo usuário
            );

            // Construção do Prompt do Usuário
            $user_prompt_lines = array();
            // translators: %s: Palavras-chave alvo para o artigo.
            $user_prompt_lines[] = sprintf( __("Escreva um artigo de blog completo e informativo sobre o seguinte tópico: \"%s\".", 'autoblogpro'), $this->target_keywords );
            // translators: %s: Idioma do artigo. %d: Número estimado de palavras.
            $user_prompt_lines[] = sprintf( __("O artigo deve ser escrito em %s e ter aproximadamente %d palavras.", 'autoblogpro'), $this->language, $this->article_length );
            // translators: %s: Tom de voz. %s: Estilo de escrita.
            $user_prompt_lines[] = sprintf( __("Adote um tom de voz %s e um estilo de escrita %s.", 'autoblogpro'), $this->tone_of_voice, $this->writing_style );

            if ( ! empty( $this->negative_keywords ) ) {
                // translators: %s: Lista de palavras-chave negativas.
                $user_prompt_lines[] = sprintf( __("Evite estritamente mencionar ou discutir os seguintes tópicos ou palavras: %s.", 'autoblogpro'), $this->negative_keywords );
            }
            if ( $this->num_h2_sections > 0 ) {
                // translators: %d: Número de seções H2 desejadas.
                $user_prompt_lines[] = sprintf( __("O artigo deve ser estruturado em exatamente %d seções principais. Cada seção principal deve ser claramente introduzida por um subtítulo H2 (usando a formatação markdown '## Título da Seção').", 'autoblogpro'), $this->num_h2_sections );
            }
            $user_prompt_lines[] = __("Certifique-se de que o conteúdo seja original, envolvente, bem estruturado e otimizado para SEO quando apropriado. Inclua um título claro e atraente para o artigo.", 'autoblogpro');

            $full_user_prompt = implode( " ", $user_prompt_lines ); // Unir com espaço para formar um parágrafo coeso de instruções

            $body = array(
                'model'    => 'gpt-3.5-turbo',
                'messages' => array(
                    array('role' => 'system', 'content' => $system_prompt),
                    array('role' => 'user', 'content' => $full_user_prompt)
                ),
                'max_tokens' => intval($this->article_length * 1.8), // Estimativa de tokens. OpenAI usa ~4 chars/token. 
                                                                // Palavras têm em média mais que 4 chars.
                                                                // Ex: 500 palavras * (5 chars/palavra) / (4 chars/token) = 625 tokens.
                                                                // Um multiplicador de 1.5 a 2.0 sobre o número de palavras pode ser uma heurística.
                'temperature' => floatval($this->creativity),
                // 'n' => 1, // Número de conclusões a gerar (default 1)
                // 'top_p' => 1, // Alternativa ao temperature (nucleus sampling)
            );

            $args = array(
                'body'    => wp_json_encode( $body ), // Garante que o corpo seja JSON
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                ),
                'timeout' => 120, // Timeout aumentado para chamadas de API (padrão é 5s)
                // 'sslverify' => false, // Apenas para debug local se houver problemas SSL, não use em produção.
            );

            $response = wp_remote_post( $api_url, $args );

            if ( is_wp_error( $response ) ) {
                $wp_error_message = $response->get_error_message();
                AutoBP_Log_Manager::error( 'Erro na chamada wp_remote_post para OpenAI: ' . $wp_error_message, array( 'params' => $this->get_all_params_for_log(), 'article_index' => $article_index, 'error_code' => $response->get_error_code() ) );
                // Tenta identificar erros comuns de cURL que podem ser mais claros para o usuário.
                if (strpos($wp_error_message, 'cURL error 6') !== false) { // DNS lookup failure
                    return new WP_Error('openai_network_error_dns', sprintf(__('Erro de Rede: Não foi possível resolver o endereço da API OpenAI. Verifique sua conexão com a internet e configurações de DNS. Detalhes técnicos: %s', 'autoblogpro'), $wp_error_message));
                } elseif (strpos($wp_error_message, 'cURL error 7') !== false) { // Failed to connect
                    return new WP_Error('openai_network_error_connect', sprintf(__('Erro de Rede: Falha ao conectar com os servidores da API OpenAI. Verifique sua conexão ou um possível bloqueio por firewall. Detalhes técnicos: %s', 'autoblogpro'), $wp_error_message));
                } elseif (strpos($wp_error_message, 'cURL error 28') !== false) { // Operation timed out
                    return new WP_Error('openai_network_error_timeout', sprintf(__('Erro de Rede: A requisição para a API OpenAI excedeu o tempo limite de %d segundos. O servidor da API pode estar sobrecarregado ou sua conexão instável. Tente novamente mais tarde. Detalhes técnicos: %s', 'autoblogpro'), $args['timeout'], $wp_error_message));
                }
                return new WP_Error( 'openai_wp_remote_post_failed', sprintf( __( 'Falha na comunicação com a API OpenAI. Detalhes técnicos: %s', 'autoblogpro' ), $wp_error_message ) );
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );
            
            if ( empty($response_body) ) {
                AutoBP_Log_Manager::error( 'Resposta vazia da API OpenAI.', array( 'response_code' => $response_code, 'params' => $this->get_all_params_for_log(), 'article_index' => $article_index ) );
                return new WP_Error( 'openai_empty_response_body', sprintf(__( 'A API OpenAI retornou uma resposta vazia (Código HTTP: %d). Isso pode indicar um problema temporário com a API. Verifique o status da API OpenAI e tente novamente.', 'autoblogpro' ), $response_code ));
            }
            
            $data = json_decode( $response_body, true );

            if ( isset($data['usage']['total_tokens']) && class_exists('AutoBlogPro') ) {
                AutoBlogPro::add_to_openai_token_usage( (int) $data['usage']['total_tokens'] );
            }

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                AutoBP_Log_Manager::error( 'Falha ao decodificar JSON da API OpenAI: ' . json_last_error_msg(), array( 'response_body_snippet' => mb_substr($response_body, 0, 500), 'params' => $this->get_all_params_for_log(), 'article_index' => $article_index ) );
                return new WP_Error( 'openai_json_decode_failed', sprintf(__( 'Falha ao processar a resposta da API OpenAI: Não foi possível decodificar o JSON. Erro: %s.', 'autoblogpro' ), json_last_error_msg()) );
            }
            
            if ( $response_code !== 200 ) {
                $api_error_message = __( 'Ocorreu um erro desconhecido ao comunicar com a API OpenAI.', 'autoblogpro' );
                $api_error_type = 'unknown_api_error';
                if ( ! empty( $data['error'] ) ) {
                    $api_error_message = isset( $data['error']['message'] ) ? trim( $data['error']['message'] ) : $api_error_message;
                    $api_error_type = isset( $data['error']['type'] ) ? sanitize_key( $data['error']['type'] ) : $api_error_type;
                }
                AutoBP_Log_Manager::error( 'Erro da API OpenAI: ' . $api_error_message, array( 'response_code' => $response_code, 'api_error_type' => $api_error_type, 'response_body' => $data, 'params' => $this->get_all_params_for_log(), 'article_index' => $article_index ) );

                if ( $response_code === 401 ) {
                    return new WP_Error( 'openai_auth_error', sprintf( __( 'Erro de Autenticação com a API OpenAI (HTTP %2$d): %1$s. Verifique se sua chave de API está correta e ativa nas configurações do plugin.', 'autoblogpro' ), $api_error_message, $response_code ), array('status' => $response_code) );
                } elseif ( $response_code === 429 ) {
                    return new WP_Error( 'openai_quota_error', sprintf( __( 'Limite de Cota/Taxa da API OpenAI Excedido (HTTP %2$d): %1$s. Verifique seu plano e uso na plataforma OpenAI.', 'autoblogpro' ), $api_error_message, $response_code ), array('status' => $response_code) );
                } elseif ( $response_code >= 500 ) {
                     return new WP_Error( 'openai_server_error', sprintf( __( 'Erro no Servidor da API OpenAI (HTTP %2$d): %1$s. Isso geralmente é um problema temporário com a OpenAI. Tente novamente mais tarde.', 'autoblogpro' ), $api_error_message, $response_code ), array('status' => $response_code) );
                }
                return new WP_Error( 'openai_api_error_generic', sprintf( __( 'A API OpenAI retornou um erro (HTTP %1$d - Tipo: %2$s): %3$s', 'autoblogpro' ), $response_code, $api_error_type, $api_error_message ), array('status' => $response_code) );
            }

            if ( isset( $data['choices'][0]['message']['content'] ) ) {
                $generated_content = trim( $data['choices'][0]['message']['content'] );
                AutoBP_Log_Manager::info( 'Conteúdo recebido da API OpenAI com sucesso.', array( 'params' => $this->get_all_params_for_log(), 'article_index' => $article_index, 'snippet' => mb_substr($generated_content, 0, 100) . '...' ) );
                if( empty( $generated_content ) ) {
                    AutoBP_Log_Manager::warning( 'Conteúdo gerado pela API OpenAI está vazio.', array( 'params' => $this->get_all_params_for_log(), 'article_index' => $article_index, 'api_response_data' => $data ) );
                    return new WP_Error( 'openai_empty_content_generated', __( 'Resposta da API: O conteúdo gerado pela API OpenAI está vazio, embora a requisição tenha sido bem-sucedida.', 'autoblogpro' ) );
                }

                // Conteúdo recebido, agora vamos criar o post
                $base_post_title = sanitize_text_field( "Artigo sobre: " . $this->target_keywords );
                
                // Adiciona um sufixo ao título se estiver gerando múltiplos artigos neste lote
                // e o total de artigos no lote ($this->num_articles) for maior que 1.
                $final_post_title = $base_post_title;
                if ( $this->num_articles > 1 ) {
                    $final_post_title .= " (#" . $article_index . "/" . $this->num_articles . ")";
                }

                // Trunca o título final se for muito longo.
                if ( mb_strlen( $final_post_title ) > 200 ) { // Limite arbitrário para o comprimento do título
                    $final_post_title = mb_substr( $final_post_title, 0, 197 ) . '...';
                }

                $post_data = array(
                    'post_title'    => $final_post_title,
                    'post_content'  => wp_kses_post( $generated_content ),
                    'post_status'   => 'draft',
                    'post_author'   => get_current_user_id(),
                    'post_type'     => 'post',
                );

                $new_post_id = wp_insert_post( $post_data, true ); 

                if ( is_wp_error( $new_post_id ) ) {
                    AutoBP_Log_Manager::error( 'Falha ao salvar post (wp_insert_post): ' . $new_post_id->get_error_message(), array('params' => $this->get_all_params_for_log(), 'article_index' => $article_index, 'post_data_title' => $post_data['post_title'], 'error_code' => $new_post_id->get_error_code() ) );
                    return new WP_Error( 'wp_insert_post_failed', sprintf( __( 'Erro Interno no WordPress: Falha ao salvar o rascunho do artigo. Detalhes: %s', 'autoblogpro' ), $new_post_id->get_error_message() ) );
                }

                if ( $new_post_id === 0 ) { 
                     AutoBP_Log_Manager::error( 'Falha ao salvar post (wp_insert_post retornou 0).', array('params' => $this->get_all_params_for_log(), 'article_index' => $article_index, 'post_data_title' => $post_data['post_title'] ) );
                     return new WP_Error( 'wp_insert_post_returned_zero', __( 'Erro Interno no WordPress: Falha ao salvar o rascunho do artigo (a função de inserção retornou 0).', 'autoblogpro' ) );
                }
                AutoBP_Log_Manager::info( 'Novo artigo salvo como rascunho.', array( 'post_id' => $new_post_id, 'title' => $final_post_title, 'params' => $this->get_all_params_for_log(), 'article_index' => $article_index ) );

                // Salvar ID do nicho como post meta
                update_post_meta( $new_post_id, '_autobp_generated_from_niche_id', $this->niche_id );

                // Salvar todos os parâmetros de geração
                update_post_meta( $new_post_id, '_autobp_generation_mode', 'automatic' );
                // $this->target_keywords é um array, salvar como string ou como array?
                // Se for array, wp_set_post_terms poderia ser usado se fossem taxonomias.
                // Para meta, se for array, ele será serializado. Para consistência com como é recebido no form (string),
                // talvez seja melhor salvar como string.
                $keywords_string_for_meta = is_array($this->target_keywords) ? implode(', ', $this->target_keywords) : $this->target_keywords;
                update_post_meta( $new_post_id, '_autobp_gen_params_target_keywords', $keywords_string_for_meta );
                update_post_meta( $new_post_id, '_autobp_gen_params_article_length', $this->article_length );
                update_post_meta( $new_post_id, '_autobp_gen_params_tone_of_voice', $this->tone_of_voice );
                update_post_meta( $new_post_id, '_autobp_gen_params_writing_style', $this->writing_style );
                update_post_meta( $new_post_id, '_autobp_gen_params_creativity', $this->creativity );
                update_post_meta( $new_post_id, '_autobp_gen_params_language', $this->language );
                update_post_meta( $new_post_id, '_autobp_gen_params_num_h2_sections', $this->num_h2_sections );
                // $this->num_articles é o total do lote, não necessariamente para este post individual se o chamador controlar o loop.
                // Mas, para regenerar um único, pode ser útil saber se ele foi parte de um lote maior.
                // No entanto, para regenerar *este* post, o número de artigos do lote original não é diretamente usado.
                // O que importa são os parâmetros que definiram *este* post específico.
                // Se o título foi formatado com (#X/Y), isso já está no título.

                // Definir categorias do post se houver
                $niche_categories = get_post_meta( $this->niche_id, '_niche_associated_categories', true );
                if ( ! empty( $niche_categories ) && is_array( $niche_categories ) ) {
                    $sanitized_niche_categories = array_map( 'intval', $niche_categories );
                    wp_set_post_categories( $new_post_id, $sanitized_niche_categories );
                }

                // Gerar meta descrição se a opção estiver habilitada
                if ( $this->generate_meta_desc ) {
                    $post_content_for_meta = get_post_field( 'post_content', $new_post_id );
                    if ( ! empty( $post_content_for_meta ) ) {
                        $this->_generate_and_save_meta_description( $new_post_id, $post_content_for_meta, $this->language );
                        // O resultado de _generate_and_save_meta_description (true/false) não é crítico para o fluxo principal aqui.
                        // O artigo principal já foi criado. A falha na meta descrição pode ser logada internamente.
                    }
                }

                // Gerar e salvar sugestões de título
                $post_content_for_titles = get_post_field( 'post_content', $new_post_id );
                if ( !empty($post_content_for_titles) ) {
                    // Assegurar que target_keywords seja uma string para o método de sugestão de títulos
                    $keywords_str_for_titles = is_array($this->target_keywords) ? implode(', ', $this->target_keywords) : $this->target_keywords;
                    $this->_generate_and_save_title_suggestions( $new_post_id, $post_content_for_titles, $keywords_str_for_titles, $this->language );
                    // A falha na geração de títulos não impede o sucesso da geração do artigo.
                } else {
                    AutoBP_Log_Manager::warning( 'Conteúdo do post vazio, pulando geração de sugestões de título.', array( 'post_id' => $new_post_id ) );
                }

                // Encontrar e salvar sugestões de links internos
                if ( class_exists( 'AutoBlogPro' ) && method_exists( 'AutoBlogPro', 'find_internal_link_suggestions' ) ) {
                    // $post_content_for_meta já foi obtido acima se generate_meta_desc era true.
                    // Se não, precisamos obtê-lo aqui.
                    if ( !isset($post_content_for_meta) || empty($post_content_for_meta) ) {
                         $post_content_for_links = get_post_field( 'post_content', $new_post_id );
                    } else {
                         $post_content_for_links = $post_content_for_meta;
                    }

                    if ( !empty( $post_content_for_links ) ) {
                        $link_suggestions = AutoBlogPro::find_internal_link_suggestions( $post_content_for_links, $new_post_id );
                        if ( ! empty( $link_suggestions ) ) {
                            update_post_meta( $new_post_id, '_autobp_internal_link_suggestions', $link_suggestions );
                            AutoBP_Log_Manager::info( 'Sugestões de links internos salvas.', array( 'post_id' => $new_post_id, 'suggestions_count' => count($link_suggestions) ) );
                        } else {
                            AutoBP_Log_Manager::info( 'Nenhuma sugestão de link interno encontrada.', array( 'post_id' => $new_post_id ) );
                        }
                    } else {
                        AutoBP_Log_Manager::info( 'Conteúdo do post vazio, pulando sugestões de links internos.', array( 'post_id' => $new_post_id ) );
                    }
                } else {
                     AutoBP_Log_Manager::debug( 'Classe AutoBlogPro ou método find_internal_link_suggestions não encontrado.', array('post_id' => $new_post_id) );
                }

                // Calcular e salvar densidade da palavra-chave foco
                if ( class_exists( 'AutoBlogPro' ) && method_exists( 'AutoBlogPro', 'calculate_keyword_density' ) ) {
                    $focus_keyword = '';
                    if ( is_array( $this->target_keywords ) && ! empty( $this->target_keywords ) ) {
                        $focus_keyword = trim( $this->target_keywords[0] );
                    } elseif ( is_string( $this->target_keywords ) && ! empty( trim( $this->target_keywords ) ) ) {
                        $keywords_array = array_map('trim', explode(',', $this->target_keywords));
                        if (!empty($keywords_array[0])) {
                            $focus_keyword = $keywords_array[0];
                        }
                    }

                    if ( !empty($focus_keyword) ) {
                        // $post_content_for_links foi obtido anteriormente para sugestões de links. Reutilizar se disponível.
                        $content_for_density = isset($post_content_for_links) && !empty($post_content_for_links) ? $post_content_for_links : get_post_field('post_content', $new_post_id);
                        if (!empty($content_for_density)) {
                            $density = AutoBlogPro::calculate_keyword_density( $content_for_density, $focus_keyword );
                            if ( $density !== false ) {
                                update_post_meta( $new_post_id, '_autobp_focus_keyword', sanitize_text_field( $focus_keyword ) );
                                update_post_meta( $new_post_id, '_autobp_keyword_density', $density );
                                AutoBP_Log_Manager::info( 'Densidade da palavra-chave foco calculada e salva.', array( 'post_id' => $new_post_id, 'focus_keyword' => $focus_keyword, 'density' => $density ) );
                            }
                        } else {
                            AutoBP_Log_Manager::warning( 'Conteúdo do post vazio, não foi possível calcular densidade da palavra-chave.', array( 'post_id' => $new_post_id, 'focus_keyword' => $focus_keyword ) );
                        }
                    } else {
                        AutoBP_Log_Manager::info( 'Nenhuma palavra-chave foco primária determinada para cálculo de densidade.', array( 'post_id' => $new_post_id, 'target_keywords_param' => $this->target_keywords ) );
                    }
                } else {
                    AutoBP_Log_Manager::debug( 'Método calculate_keyword_density não encontrado na classe AutoBlogPro.', array('post_id' => $new_post_id) );
                }

                // Gerar e salvar sugestões de links externos
                if ( class_exists( 'AutoBlogPro' ) && method_exists( 'AutoBlogPro', 'fetch_external_link_suggestions' ) && !empty( $this->api_key ) ) {
                    // $content_for_density já tem o conteúdo do post. Reutilizar.
                    // $keywords_string_for_meta já tem as keywords como string. Reutilizar.
                    if (!empty($content_for_density) && !empty($keywords_string_for_meta)) {
                        $post_content_snippet = mb_substr( $content_for_density, 0, 1500 );
                        $external_link_suggestions = AutoBlogPro::fetch_external_link_suggestions( $post_content_snippet, $keywords_string_for_meta, $this->language, $this->api_key );
                        if ( ! empty( $external_link_suggestions ) ) {
                           update_post_meta( $new_post_id, '_autobp_external_link_suggestions', $external_link_suggestions );
                           AutoBP_Log_Manager::info( 'Sugestões de links externos salvas.', array( 'post_id' => $new_post_id, 'suggestions_count' => count($external_link_suggestions) ) );
                        }
                    } else {
                        AutoBP_Log_Manager::info( 'Conteúdo ou palavras-chave vazios, pulando sugestões de links externos.', array( 'post_id' => $new_post_id ) );
                    }
                } else {
                    AutoBP_Log_Manager::debug( 'Método fetch_external_link_suggestions não encontrado ou API key ausente.', array('post_id' => $new_post_id, 'api_key_empty' => empty($this->api_key)) );
                }

                // Preencher campos de SEO para plugins (Yoast, Rank Math)
                if ( class_exists( 'AutoBlogPro' ) && method_exists( 'AutoBlogPro', 'maybe_fill_seo_plugin_fields' ) ) {
                    $saved_focus_kw = get_post_meta( $new_post_id, '_autobp_focus_keyword', true );
                    $saved_meta_desc = get_post_meta( $new_post_id, '_autobp_meta_description', true );
                    if ($saved_focus_kw && $saved_meta_desc) {
                        AutoBlogPro::maybe_fill_seo_plugin_fields( $new_post_id, $saved_focus_kw, $saved_meta_desc );
                    } else {
                        AutoBP_Log_Manager::info( 'Palavra-chave foco ou meta descrição não disponíveis para preenchimento de plugin SEO.', array('post_id' => $new_post_id, 'has_kw' => !empty($saved_focus_kw), 'has_meta' => !empty($saved_meta_desc)) );
                    }
                } else {
                     AutoBP_Log_Manager::debug( 'Método maybe_fill_seo_plugin_fields não encontrado na classe AutoBlogPro.', array('post_id' => $new_post_id) );
                }


                return $new_post_id; 

            } else {
                AutoBP_Log_Manager::error( 'Resposta da API OpenAI não continha o campo esperado `choices[0][message][content]`.', array( 'api_response_data' => $data, 'params' => $this->get_all_params_for_log(), 'article_index' => $article_index ) );
                // Log para depuração se a estrutura da resposta não for a esperada
                if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('[AutoBlogPro DEBUG] Resposta da API OpenAI não continha o campo esperado (`choices[0][message][content]`): ' . $response_body);
                }
                return new WP_Error( 'openai_missing_content_field', __( 'Resposta da API Inválida: A estrutura da resposta da API OpenAI é inesperada e o campo de conteúdo não foi encontrado.', 'autoblogpro' ) );
            }
        }

        /**
         * Gera e salva uma meta descrição otimizada para SEO para um post específico.
         *
         * Este método utiliza a API OpenAI para gerar uma meta descrição com base no conteúdo
         * textual fornecido. A meta descrição é então sanitizada e salva como metadado do post.
         *
         * @since 0.2.0
         * @access private
         * @param int    $post_id_to_update     ID do post para o qual a meta descrição será salva.
         * @param string $text_content_for_meta Conteúdo textual do post (idealmente os primeiros 3000-4000 caracteres)
         *                                      para basear a geração da meta descrição.
         * @param string $target_language       Idioma em que a meta descrição deve ser gerada (ex: 'pt-BR').
         * @return bool True se a meta descrição foi gerada e salva com sucesso, false caso contrário.
         */
        private function _generate_and_save_meta_description( $post_id_to_update, $text_content_for_meta, $target_language ) {
            AutoBP_Log_Manager::info( 'Iniciando geração de meta descrição para o post ID: ' . $post_id_to_update, array( 'post_id' => $post_id_to_update, 'target_language' => $target_language, 'text_length' => mb_strlen($text_content_for_meta) ) );

            if ( empty( $this->api_key ) ) {
                AutoBP_Log_Manager::warning( 'Geração de meta descrição falhou: API Key não definida.', array( 'post_id' => $post_id_to_update ) );
                // Log opcional: API key não disponível para meta descrição.
                if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('[AutoBlogPro DEBUG] API Key não disponível. Meta descrição não gerada para o post ID: ' . $post_id_to_update);
                }
                return false;
            }

            if ( empty( $text_content_for_meta ) ) {
                AutoBP_Log_Manager::warning( 'Geração de meta descrição falhou: Conteúdo textual vazio.', array( 'post_id' => $post_id_to_update ) );
                if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('[AutoBlogPro DEBUG] Conteúdo textual vazio. Meta descrição não gerada para o post ID: ' . $post_id_to_update);
                }
                return false;
            }

            // Limitar o texto para evitar exceder limites de tokens da API e manter o prompt focado.
            $trimmed_content = mb_substr( $text_content_for_meta, 0, 4000 );

            // Prompt para a API OpenAI
            // translators: %1$s: Idioma alvo (ex: "Português (Brasil)"). %2$s: Trecho do conteúdo do artigo.
            $prompt_text = sprintf(
                __("Com base no seguinte texto de artigo, gere uma meta descrição otimizada para SEO em '%1$s'. A meta descrição deve ser concisa (idealmente entre 120-155 caracteres, máximo 160 caracteres), atraente e resumir os pontos principais do artigo. Texto do Artigo: %2$s", 'autoblogpro'),
                $target_language,
                $trimmed_content
            );

            $api_url = 'https://api.openai.com/v1/chat/completions';
            $api_body = array(
                'model'    => 'gpt-3.5-turbo', // Ou outro modelo se preferir (gpt-4-turbo-preview pode ser melhor, mas mais caro)
                'messages' => array(
                    array('role' => 'system', 'content' => __('Você é um especialista em SEO que cria meta descrições excelentes, concisas e atraentes.', 'autoblogpro')),
                    array('role' => 'user', 'content' => $prompt_text)
                ),
                'max_tokens'  => 80, // Suficiente para uma meta descrição (aprox. 160 caracteres / 2 = 80 tokens)
                'temperature' => 0.4, // Baixa temperatura para respostas mais focadas e menos aleatórias
                'n'           => 1,
                'stop'        => null, // Pode-se usar "\n" se a IA tender a gerar múltiplas linhas.
            );

            $api_args = array(
                'body'    => wp_json_encode( $api_body ),
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                ),
                'timeout' => 45, // Timeout para a chamada da API (em segundos)
            );

            $response = wp_remote_post( $api_url, $api_args );

            if ( is_wp_error( $response ) ) {
                AutoBP_Log_Manager::error( 'Falha na chamada wp_remote_post para API de meta descrição: ' . $response->get_error_message(), array( 'post_id' => $post_id_to_update, 'error_code' => $response->get_error_code() ) );
                // Log do erro
                if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('[AutoBlogPro ERROR] Falha na chamada da API para meta descrição (Post ID: ' . $post_id_to_update . '): ' . $response->get_error_message());
                }
                return false;
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );
            $data = json_decode( $response_body, true );

            if ( isset($data['usage']['total_tokens']) && class_exists('AutoBlogPro') ) {
                AutoBlogPro::add_to_openai_token_usage( (int) $data['usage']['total_tokens'] );
            }

            if ( $response_code === 200 && ! empty( $data['choices'][0]['message']['content'] ) ) {
                $meta_description_text = trim( $data['choices'][0]['message']['content'] );
                $meta_description_text = preg_replace( '/^"|"$/', '', $meta_description_text );
                
                $update_result = update_post_meta( $post_id_to_update, '_autobp_meta_description', sanitize_text_field( $meta_description_text ) );
                
                if ($update_result) {
                    AutoBP_Log_Manager::info( 'Meta descrição gerada e salva com sucesso.', array( 'post_id' => $post_id_to_update, 'meta_description_snippet' => mb_substr($meta_description_text, 0, 50) . '...' ) );
                    return true;
                } else {
                    AutoBP_Log_Manager::error( 'Falha ao salvar meta descrição (update_post_meta).', array( 'post_id' => $post_id_to_update, 'meta_description_text' => $meta_description_text ) );
                    return false;
                }
            } else {
                $error_detail = isset($data['error']['message']) ? $data['error']['message'] : $response_body;
                AutoBP_Log_Manager::error( 'Erro ao gerar meta descrição via API OpenAI.', array( 'post_id' => $post_id_to_update, 'response_code' => $response_code, 'response_body_snippet' => mb_substr($response_body, 0, 200), 'api_error_detail' => $error_detail ) );
                // Log do erro ou resposta inesperada
                if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('[AutoBlogPro ERROR] Erro ao gerar meta descrição (Post ID: ' . $post_id_to_update . ') - HTTP Code: ' . $response_code . ' - Detalhes: ' . $error_detail);
                }
                return false;
            }
        }

        /**
         * Gera e salva sugestões de títulos otimizados para SEO para um post específico.
         *
         * Este método privado é chamado após a criação bem-sucedida de um artigo.
         * Ele constrói um prompt específico para a API OpenAI, solicitando 5 alternativas
         * de títulos que sejam otimizados para SEO, atraentes e relevantes, com base
         * nos primeiros ~2000 caracteres do conteúdo do artigo, nas palavras-chave alvo
         * e no idioma especificado.
         *
         * A chamada à API (`gpt-3.5-turbo`) é configurada para retornar uma única resposta
         * (`n=1`) com `max_tokens` em torno de 250 (suficiente para 5 títulos) e uma
         * `temperature` de 0.7 para equilibrar criatividade e relevância.
         *
         * A resposta da API é processada para extrair os títulos sugeridos (espera-se um
         * título por linha). Estes títulos são então limpos (remoção de espaços em branco
         * e linhas vazias) e salvos como um array no metadado `_autobp_title_suggestions`
         * do post.
         *
         * O processo é logado, incluindo o número de títulos sugeridos e quaisquer erros
         * que possam ocorrer durante a chamada à API ou ao salvar os metadados.
         * A falha na geração de sugestões de título não impede o sucesso da geração do artigo principal.
         *
         * @since 0.2.0
         * @access private
         * @param int    $post_id_to_update     ID do post para o qual as sugestões de título serão salvas.
         * @param string $text_content_for_titles Conteúdo textual do post (primeiros ~2000 caracteres) para basear a geração dos títulos.
         * @param string $target_keywords_str   String contendo as palavras-chave principais, separadas por vírgula.
         * @param string $target_language       Idioma em que os títulos devem ser gerados (ex: 'pt-BR').
         * @return bool True se as sugestões de título foram geradas e salvas com sucesso, false caso contrário.
         */
        private function _generate_and_save_title_suggestions( $post_id_to_update, $text_content_for_titles, $target_keywords_str, $target_language ) {
            AutoBP_Log_Manager::info( 'Iniciando geração de sugestões de título para o post ID: ' . $post_id_to_update, array( 'post_id' => $post_id_to_update, 'target_language' => $target_language, 'keywords' => $target_keywords_str, 'text_length' => mb_strlen($text_content_for_titles) ) );

            if ( empty( $this->api_key ) ) {
                AutoBP_Log_Manager::warning( 'Geração de sugestões de título falhou: API Key não definida.', array( 'post_id' => $post_id_to_update ) );
                return false;
            }

            if ( empty( $text_content_for_titles ) ) {
                AutoBP_Log_Manager::warning( 'Geração de sugestões de título falhou: Conteúdo textual vazio.', array( 'post_id' => $post_id_to_update ) );
                return false;
            }

            // Limitar o texto para evitar exceder limites de tokens da API e manter o prompt focado.
            $trimmed_content = mb_substr( $text_content_for_titles, 0, 2000 ); // Primeiros 2000 caracteres

            // Prompt para a API OpenAI
            $prompt_text = "Com base no seguinte conteúdo de artigo e nas palavras-chave alvo, sugira 5 alternativas de títulos que sejam otimizados para SEO, atraentes e relevantes.\n";
            $prompt_text .= "As palavras-chave alvo são: '{$target_keywords_str}'.\n";
            $prompt_text .= "O idioma dos títulos deve ser '{$target_language}'.\n";
            $prompt_text .= "Apresente cada título sugerido em uma nova linha, sem numeração ou marcadores. Apenas os títulos.\n\n";
            $prompt_text .= "Conteúdo do Artigo (resumo/início):\n{$trimmed_content}";

            $api_url = 'https://api.openai.com/v1/chat/completions';
            $api_body = array(
                'model'    => 'gpt-3.5-turbo',
                'messages' => array(
                    array('role' => 'system', 'content' => __('Você é um especialista em SEO e copywriting que cria títulos de artigos de blog altamente eficazes.', 'autoblogpro')),
                    array('role' => 'user', 'content' => $prompt_text)
                ),
                'max_tokens'  => 250, 
                'temperature' => 0.7, 
                'n'           => 1,
                'stop'        => null,
            );

            $api_args = array(
                'body'    => wp_json_encode( $api_body ),
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                ),
                'timeout' => 60, 
            );

            $response = wp_remote_post( $api_url, $api_args );

            if ( is_wp_error( $response ) ) {
                AutoBP_Log_Manager::error( 'Falha na chamada wp_remote_post para API de sugestões de título: ' . $response->get_error_message(), array( 'post_id' => $post_id_to_update, 'error_code' => $response->get_error_code() ) );
                return false;
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );
            $data = json_decode( $response_body, true );

            if ( isset($data['usage']['total_tokens']) && class_exists('AutoBlogPro') ) {
                AutoBlogPro::add_to_openai_token_usage( (int) $data['usage']['total_tokens'] );
            }

            if ( $response_code === 200 && ! empty( $data['choices'][0]['message']['content'] ) ) {
                $raw_titles_text = trim( $data['choices'][0]['message']['content'] );
                $title_suggestions_array = array_map( 'trim', explode( "\n", $raw_titles_text ) );
                $title_suggestions_array = array_filter( $title_suggestions_array ); // Remover linhas vazias

                if ( ! empty( $title_suggestions_array ) ) {
                    $update_result = update_post_meta( $post_id_to_update, '_autobp_title_suggestions', $title_suggestions_array );
                    if ($update_result) {
                        AutoBP_Log_Manager::info( count($title_suggestions_array) . ' sugestões de título geradas e salvas.', array( 'post_id' => $post_id_to_update, 'suggestions' => $title_suggestions_array ) );
                        return true;
                    } else {
                        AutoBP_Log_Manager::error( 'Falha ao salvar sugestões de título (update_post_meta).', array( 'post_id' => $post_id_to_update, 'suggestions' => $title_suggestions_array ) );
                        return false;
                    }
                } else {
                    AutoBP_Log_Manager::warning( 'Nenhuma sugestão de título foi extraída da resposta da API.', array( 'post_id' => $post_id_to_update, 'api_response_text' => $raw_titles_text ) );
                    return false;
                }
            } else {
                $error_detail = isset($data['error']['message']) ? $data['error']['message'] : $response_body;
                AutoBP_Log_Manager::error( 'Erro ao gerar sugestões de título via API OpenAI.', array( 'post_id' => $post_id_to_update, 'response_code' => $response_code, 'response_body_snippet' => mb_substr($response_body, 0, 200), 'api_error_detail' => $error_detail ) );
                return false;
            }
        }
    }
}
?>
