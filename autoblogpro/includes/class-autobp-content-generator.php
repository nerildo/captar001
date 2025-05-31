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
         */
        public function __construct( $api_key, $niche_id, $num_articles, $target_keywords, $article_length, $tone_of_voice, $writing_style, $creativity, $negative_keywords, $language, $num_h2_sections ) {
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

            // Validação inicial dos parâmetros pode ser feita aqui, se desejado,
            // ou deixar para o método generate() lidar com isso de forma mais completa.
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
         *
         * @since 0.1.0
         * @access public
         * @param int $article_index O índice do artigo atual no lote de geração (1-indexado).
         * @return int|WP_Error O ID do post gerado em caso de sucesso, ou `WP_Error` em caso de falha.
         */
        public function generate( $article_index = 1 ) {
            // Validação robusta dos parâmetros de entrada.
            if ( empty( $this->api_key ) ) {
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
                // Tenta identificar erros comuns de cURL que podem ser mais claros para o usuário.
                if (strpos($wp_error_message, 'cURL error 6') !== false) { // DNS lookup failure
                    // translators: %s: Mensagem de erro original do WordPress (cURL error 6...).
                    return new WP_Error('openai_network_error_dns', sprintf(__('Erro de Rede: Não foi possível resolver o endereço da API OpenAI. Verifique sua conexão com a internet e configurações de DNS. Detalhes técnicos: %s', 'autoblogpro'), $wp_error_message));
                } elseif (strpos($wp_error_message, 'cURL error 7') !== false) { // Failed to connect
                     // translators: %s: Mensagem de erro original do WordPress (cURL error 7...).
                    return new WP_Error('openai_network_error_connect', sprintf(__('Erro de Rede: Falha ao conectar com os servidores da API OpenAI. Verifique sua conexão ou um possível bloqueio por firewall. Detalhes técnicos: %s', 'autoblogpro'), $wp_error_message));
                } elseif (strpos($wp_error_message, 'cURL error 28') !== false) { // Operation timed out
                    // translators: %s: Mensagem de erro original do WordPress (cURL error 28...).
                    return new WP_Error('openai_network_error_timeout', sprintf(__('Erro de Rede: A requisição para a API OpenAI excedeu o tempo limite de %d segundos. O servidor da API pode estar sobrecarregado ou sua conexão instável. Tente novamente mais tarde. Detalhes técnicos: %s', 'autoblogpro'), $args['timeout'], $wp_error_message));
                }
                // translators: %s: Mensagem de erro genérica do WordPress ao tentar fazer a requisição.
                return new WP_Error( 'openai_wp_remote_post_failed', sprintf( __( 'Falha na comunicação com a API OpenAI. Detalhes técnicos: %s', 'autoblogpro' ), $wp_error_message ) );
            }

            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );

            if ( empty($response_body) ) {
                // translators: %d: Código de status HTTP.
                return new WP_Error( 'openai_empty_response_body', sprintf(__( 'A API OpenAI retornou uma resposta vazia (Código HTTP: %d). Isso pode indicar um problema temporário com a API. Verifique o status da API OpenAI e tente novamente.', 'autoblogpro' ), $response_code ));
            }

            $data = json_decode( $response_body, true );

            if ( json_last_error() !== JSON_ERROR_NONE ) {
                // translators: %s: Mensagem de erro específica do json_decode (ex: "Syntax error").
                return new WP_Error( 'openai_json_decode_failed', sprintf(__( 'Falha ao processar a resposta da API OpenAI: Não foi possível decodificar o JSON. Erro: %s.', 'autoblogpro' ), json_last_error_msg()) );
            }

            // Tratamento de erros específicos da API OpenAI com base no response_code e corpo
            if ( $response_code !== 200 ) {
                $api_error_message = __( 'Ocorreu um erro desconhecido ao comunicar com a API OpenAI.', 'autoblogpro' );
                $api_error_type = 'unknown_api_error';

                if ( ! empty( $data['error'] ) ) {
                    $api_error_message = isset( $data['error']['message'] ) ? trim( $data['error']['message'] ) : $api_error_message;
                    $api_error_type = isset( $data['error']['type'] ) ? sanitize_key( $data['error']['type'] ) : $api_error_type;
                }

                // Códigos de erro comuns da API OpenAI:
                // 401: Invalid Authentication - Chave de API incorreta ou ausente.
                // 429: Rate limit reached / Quota exceeded - Limite de taxa ou cota excedida.
                // 500: Internal Server Error - Problema no servidor da OpenAI.
                // 503: Service Unavailable - Problema temporário no servidor da OpenAI.
                if ( $response_code === 401 ) {
                    // translators: %1$s: Mensagem de erro específica da API. %2$d: Código HTTP.
                    return new WP_Error( 'openai_auth_error', sprintf( __( 'Erro de Autenticação com a API OpenAI (HTTP %2$d): %1$s. Verifique se sua chave de API está correta e ativa nas configurações do plugin.', 'autoblogpro' ), $api_error_message, $response_code ), array('status' => $response_code) );
                } elseif ( $response_code === 429 ) {
                     // translators: %1$s: Mensagem de erro específica da API. %2$d: Código HTTP.
                    return new WP_Error( 'openai_quota_error', sprintf( __( 'Limite de Cota/Taxa da API OpenAI Excedido (HTTP %2$d): %1$s. Verifique seu plano e uso na plataforma OpenAI.', 'autoblogpro' ), $api_error_message, $response_code ), array('status' => $response_code) );
                } elseif ( $response_code >= 500 ) {
                    // translators: %1$s: Mensagem de erro específica da API. %2$d: Código HTTP.
                     return new WP_Error( 'openai_server_error', sprintf( __( 'Erro no Servidor da API OpenAI (HTTP %2$d): %1$s. Isso geralmente é um problema temporário com a OpenAI. Tente novamente mais tarde.', 'autoblogpro' ), $api_error_message, $response_code ), array('status' => $response_code) );
                }

                // translators: %1$d: Código de status HTTP, %2$s: Tipo de erro da API (se houver), %3$s: Mensagem de erro da API.
                return new WP_Error( 'openai_api_error_generic', sprintf( __( 'A API OpenAI retornou um erro (HTTP %1$d - Tipo: %2$s): %3$s', 'autoblogpro' ), $response_code, $api_error_type, $api_error_message ), array('status' => $response_code) );
            }

            if ( isset( $data['choices'][0]['message']['content'] ) ) {
                $generated_content = trim( $data['choices'][0]['message']['content'] );
                if( empty( $generated_content ) ) {
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

                $new_post_id = wp_insert_post( $post_data, true ); // Passar true para retornar WP_Error em falha

                if ( is_wp_error( $new_post_id ) ) {
                    // translators: %s: Mensagem de erro do WordPress ao tentar inserir o post.
                    return new WP_Error( 'wp_insert_post_failed', sprintf( __( 'Erro Interno no WordPress: Falha ao salvar o rascunho do artigo. Detalhes: %s', 'autoblogpro' ), $new_post_id->get_error_message() ) );
                }

                if ( $new_post_id === 0 ) { // Checagem de segurança, embora wp_insert_post com $wp_error=true deva retornar WP_Error.
                     return new WP_Error( 'wp_insert_post_returned_zero', __( 'Erro Interno no WordPress: Falha ao salvar o rascunho do artigo (a função de inserção retornou 0).', 'autoblogpro' ) );
                }

                // Salvar ID do nicho como post meta
                update_post_meta( $new_post_id, '_autobp_generated_from_niche_id', $this->niche_id );

                // Definir categorias do post se houver
                $niche_categories = get_post_meta( $this->niche_id, '_niche_associated_categories', true );
                if ( ! empty( $niche_categories ) && is_array( $niche_categories ) ) {
                    $sanitized_niche_categories = array_map( 'intval', $niche_categories );
                    wp_set_post_categories( $new_post_id, $sanitized_niche_categories );
                }

                return $new_post_id; // Retorna o ID do novo post

            } else {
                // Log para depuração se a estrutura da resposta não for a esperada
                if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log('[AutoBlogPro DEBUG] Resposta da API OpenAI não continha o campo esperado (`choices[0][message][content]`): ' . $response_body);
                }
                return new WP_Error( 'openai_missing_content_field', __( 'Resposta da API Inválida: A estrutura da resposta da API OpenAI é inesperada e o campo de conteúdo não foi encontrado.', 'autoblogpro' ) );
            }
        }
    }
}
?>
