<?php
/**
 * Gerencia as páginas de administração do plugin AutoBlogPro.
 *
 * Esta classe é responsável por criar o menu principal do plugin,
 * a subpágina de Configurações (para a chave da API OpenAI) e a
 * subpágina de Geração de Artigos. Utiliza a API de Configurações do WordPress
 * para o salvamento da chave da API.
 *
 * @package     AutoBlogPro
 * @subpackage  Admin
 * @since       0.1.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Classe AutoBP_Settings_Page.
 *
 * Responsável por toda a interface administrativa do plugin AutoBlogPro,
 * incluindo menus, páginas de configurações e formulários.
 */
class AutoBP_Settings_Page {

    /**
     * Identificador do grupo de opções usado na API de Configurações do WordPress.
     *
     * @since 0.1.0
     * @access private
     * @var string
     */
    private static $option_group = 'autobp_settings_group';

    /**
     * Nome da opção no banco de dados onde a chave da API OpenAI é armazenada.
     *
     * @since 0.1.0
     * @access private
     * @var string
     */
    private static $api_key_option_name = 'autobp_openai_api_key';

    /**
     * Slug da subpágina de Configurações.
     *
     * @since 0.1.0
     * @access private
     * @var string
     */
    private static $settings_page_slug = 'autobp-settings';

    /**
     * Slug do menu principal do plugin.
     *
     * @since 0.1.0
     * @access private
     * @var string
     */
    private static $main_menu_slug = 'autoblogpro';

    /**
     * Slug da subpágina de Geração de Artigos.
     *
     * @since 0.1.0
     * @access private
     * @var string
     */
    private static $generate_page_slug = 'autobp-generate-articles';

    /**
     * Inicializa os hooks do WordPress para adicionar os menus e registrar as configurações.
     *
     * Adiciona actions para 'admin_menu' e 'admin_init'.
     *
     * @since 0.1.0
     * @access public
     * @static
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    /**
     * Adiciona os itens de menu principal e submenus no painel administrativo do WordPress.
     *
     * Chamado pelo hook 'admin_menu'.
     * Cria o menu "AutoBlogPro" e os submenus "Configurações" e "Gerar Artigos".
     *
     * @since 0.1.0
     * @access public
     * @static
     */
    public static function add_admin_menu() {
        add_menu_page(
            __( 'AutoBlogPro', 'autoblogpro' ), // Título da Página
            __( 'AutoBlogPro', 'autoblogpro' ),
            'manage_options',
            self::$main_menu_slug,                              // Slug do Menu
            array( __CLASS__, 'render_main_page_placeholder' ), // Callback para renderizar a página principal
            'dashicons-robot',                                  // Ícone do Menu
            85                                                  // Posição no menu
        );

        add_submenu_page(
            self::$main_menu_slug,                              // Slug do Menu Pai
            __( 'Configurações - AutoBlogPro', 'autoblogpro' ), // Título da Página
            __( 'Configurações', 'autoblogpro' ),               // Título do Menu
            'manage_options',                                   // Capacidade necessária
            self::$settings_page_slug,                          // Slug do Menu
            array( __CLASS__, 'render_settings_page' )          // Callback para renderizar a página
        );

        add_submenu_page(
            self::$main_menu_slug,                              // Slug do Menu Pai
            __( 'Gerar Artigos - AutoBlogPro', 'autoblogpro' ), // Título da Página
            __( 'Gerar Artigos', 'autoblogpro' ),               // Título do Menu
            'manage_options',                                   // Capacidade necessária
            self::$generate_page_slug,                          // Slug do Menu (usando a propriedade de classe)
            array( __CLASS__, 'render_generate_articles_page' ) // Callback para renderizar a página
        );
    }

    /**
     * Renderiza o conteúdo placeholder para a página principal do menu AutoBlogPro.
     *
     * Atualmente, exibe uma mensagem de boas-vindas e um link para a página de configurações.
     *
     * @since 0.1.0
     * @access public
     * @static
     */
    public static function render_main_page_placeholder() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'AutoBlogPro', 'autoblogpro' ); ?></h1>
            <p><?php esc_html_e( 'Bem-vindo ao AutoBlogPro. Por favor, navegue até a seção de Configurações para gerenciar sua chave de API.', 'autoblogpro' ); ?></p>
            <p><a href="<?php echo esc_url(admin_url('admin.php?page=' . self::$settings_page_slug)); ?>" class="button button-primary">
                <?php esc_html_e( 'Ir para Configurações', 'autoblogpro' ); ?>
            </a></p>
        </div>
        <?php
    }


    /**
     * Registra as configurações do plugin usando a API de Configurações do WordPress.
     *
     * Chamado pelo hook 'admin_init'.
     * Registra a opção 'autobp_openai_api_key', a seção e o campo correspondente.
     *
     * @since 0.1.0
     * @access public
     * @static
     */
    public static function register_settings() {
        register_setting(
            self::$option_group,                          // Grupo de opções
            self::$api_key_option_name,
            array( __CLASS__, 'sanitize_api_key' )        // Callback de sanitização
        );

        add_settings_section(
            'autobp_openai_section',                      // ID da Seção
            __( 'Configurações da API OpenAI', 'autoblogpro' ), // Título da Seção
            null,                                         // Callback para exibir HTML antes dos campos da seção (opcional)
            self::$settings_page_slug                     // Página onde a seção será exibida
        );

        add_settings_field(
            'autobp_openai_api_key_field',                // ID do Campo
            __( 'Chave da API OpenAI', 'autoblogpro' ),   // Título do Campo
            array( __CLASS__, 'render_api_key_field' ),   // Callback para renderizar o campo
            self::$settings_page_slug,                    // Página onde o campo será exibido
            'autobp_openai_section'                       // Seção à qual o campo pertence
        );
    }

    /**
     * Renderiza o HTML para a subpágina de Configurações.
     *
     * Exibe o formulário que permite ao usuário salvar a chave da API OpenAI.
     * Utiliza `settings_fields()` e `do_settings_sections()`.
     *
     * @since 0.1.0
     * @access public
     * @static
     */
    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <?php settings_errors(); // Exibe mensagens de erro/sucesso para a página de config ?>
            <form action="options.php" method="post">
                <?php
                settings_fields( self::$option_group );
                do_settings_sections( self::$settings_page_slug );
                submit_button( __( 'Salvar Configurações', 'autoblogpro' ) );
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renderiza o campo de input HTML para a chave da API OpenAI.
     *
     * Recupera o valor salvo da opção e o exibe no campo.
     *
     * @since 0.1.0
     * @access public
     * @static
     */
    public static function render_api_key_field() {
        $api_key = get_option( self::$api_key_option_name, '' );
        ?>
        <input type="text" name="<?php echo esc_attr( self::$api_key_option_name ); ?>" id="<?php echo esc_attr( self::$api_key_option_name ); ?>" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text">
        <p class="description"><?php esc_html_e( 'Insira sua chave da API OpenAI aqui.', 'autoblogpro' ); ?></p>
        <?php
    }

    /**
     * Sanitiza o valor da chave da API OpenAI antes de salvá-la no banco de dados.
     *
     * Utiliza `sanitize_text_field` para remover tags HTML e outros elementos potencialmente perigosos.
     *
     * @since 0.1.0
     * @access public
     * @static
     * @param string $input O valor bruto da chave da API enviado pelo formulário.
     * @return string A chave da API sanitizada.
     */
    public static function sanitize_api_key( $input ) {
        return sanitize_text_field( $input );
    }

    /**
     * Renderiza o HTML para a subpágina de Geração de Artigos.
     *
     * Exibe um formulário com campos para definir os parâmetros da geração de conteúdo,
     * como nicho, palavras-chave, tamanho do artigo, tom de voz, estilo, criatividade,
     * palavras-chave negativas e idioma.
     *
     * Ao submeter o formulário, os dados são coletados, sanitizados e passados para
     * a classe `AutoBP_Content_Generator` para processar a geração.
     * O feedback (sucesso ou erro) é exibido na mesma página.
     *
     * @since 0.1.0
     * @access public
     * @static
     */
    public static function render_generate_articles_page() {
        // Processamento do formulário de geração de artigos
        if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
            // Verificação de segurança usando nonce.
            if ( isset( $_POST['autobp_generate_articles_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['autobp_generate_articles_nonce'] ) ), 'autobp_generate_articles_action' ) ) {

                // Coleta e sanitização dos dados do formulário.
                $niche_id = isset( $_POST['autobp_niche_id'] ) ? intval( $_POST['autobp_niche_id'] ) : 0;
                $num_articles = isset( $_POST['autobp_num_articles'] ) ? intval( $_POST['autobp_num_articles'] ) : 1; // Default 1, embora a geração seja 1 por vez.
                $target_keywords = isset( $_POST['autobp_target_keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['autobp_target_keywords'] ) ) : '';

                // Coleta e sanitização dos novos campos de personalização.
                $article_length = isset( $_POST['autobp_article_length'] ) ? intval( $_POST['autobp_article_length'] ) : 500; // Default 500 palavras.
                $tone_of_voice = isset( $_POST['autobp_tone_of_voice'] ) ? sanitize_key( $_POST['autobp_tone_of_voice'] ) : 'neutro'; // sanitize_key para valores de select.
                $writing_style = isset( $_POST['autobp_writing_style'] ) ? sanitize_key( $_POST['autobp_writing_style'] ) : 'informativo';
                $creativity = isset( $_POST['autobp_creativity'] ) ? floatval( $_POST['autobp_creativity'] ) : 0.7; // floatval para o range slider.
                // Garante que a criatividade esteja dentro de um limite esperado pelo formulário (0.1-1.0).
                // A API OpenAI pode aceitar até 2.0 para 'temperature'.
                if ($creativity < 0.1) $creativity = 0.1;
                if ($creativity > 1.0) $creativity = 1.0;
                $negative_keywords = isset( $_POST['autobp_negative_keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['autobp_negative_keywords'] ) ) : '';
                $language = isset( $_POST['autobp_language'] ) ? sanitize_text_field( wp_unslash( $_POST['autobp_language'] ) ) : 'pt-BR'; // Default 'pt-BR'.

                // Recupera a chave da API OpenAI salva nas configurações.
                $api_key = get_option( self::$api_key_option_name, '' );

                // Carrega a classe AutoBP_Content_Generator se ainda não estiver carregada.
                if ( ! class_exists( 'AutoBP_Content_Generator' ) ) {
                    if ( file_exists( AUTOBP_PLUGIN_DIR . 'includes/class-autobp-content-generator.php' ) ) {
                        require_once AUTOBP_PLUGIN_DIR . 'includes/class-autobp-content-generator.php';
                    } else {
                        // Exibir erro se o arquivo da classe AutoBP_Content_Generator não for encontrado.
                        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Erro crítico: Arquivo do gerador de conteúdo não encontrado.', 'autoblogpro' ) . '</p></div>';
                        // Não prosseguir se a classe não puder ser carregada.
                        $niche_id = 0; // Reseta $niche_id para evitar tentativa de geração.
                    }
                }

                // Apenas prossegue se a classe AutoBP_Content_Generator existir e os dados básicos (nicho, palavras-chave) forem válidos.
                // num_articles é passado, mas a classe ContentGenerator ainda gera 1 por vez.
                if ( class_exists('AutoBP_Content_Generator') && $niche_id > 0 && !empty($target_keywords) ) {
                    $generator = new AutoBP_Content_Generator(
                        $api_key,
                        $niche_id,
                        $num_articles,
                        $target_keywords,
                        $article_length,
                        $tone_of_voice,
                        $writing_style,
                        $creativity,
                        $negative_keywords,
                        $language
                    );
                    $result = $generator->generate(); // Chama o método de geração.

                    // Exibe feedback para o usuário com base no resultado da geração.
                    // Instancia o gerador de conteúdo com todos os parâmetros coletados.
                    $generator = new AutoBP_Content_Generator(
                        $api_key,
                        $niche_id,
                        $num_articles,
                        $target_keywords,
                        $article_length,
                        $tone_of_voice,
                        $writing_style,
                        $creativity,
                        $negative_keywords,
                        $language
                    );
                    $result = $generator->generate(); // Chama o método de geração.

                    // Exibe feedback (sucesso ou erro) para o usuário.
                    if ( is_wp_error( $result ) ) {
                        // Se $result for um WP_Error, exibe a mensagem de erro.
                        $error_code = $result->get_error_code();
                        $error_message = $result->get_error_message();
                        // translators: %1$s: Código do erro (ex: 'api_key_missing'), %2$s: Mensagem de erro detalhada.
                        $formatted_error = sprintf(
                            '<strong>%1$s:</strong> %2$s', // Exibe o código do erro para referência.
                            esc_html( $error_code ),
                            esc_html( $error_message )
                        );
                        echo '<div class="notice notice-error is-dismissible"><p>' . wp_kses_post( $formatted_error ) . '</p></div>';
                    } elseif ( is_int( $result ) && $result > 0 ) {
                        // Se $result for um ID de post válido (inteiro > 0), exibe mensagem de sucesso.
                        $edit_link = get_edit_post_link( $result ); // Obtém o link de edição para o rascunho.
                        $post_title = get_the_title( $result );    // Obtém o título do rascunho.
                        $success_message = sprintf(
                            // translators: %1$s: Título do post gerado, %2$s: URL para editar o post.
                            wp_kses_post( __( 'Artigo "%1$s" gerado e salvo como rascunho com sucesso! <a href="%2$s" target="_blank">Editar rascunho</a>.', 'autoblogpro' ) ),
                            esc_html( $post_title ),
                            esc_url( $edit_link )
                        );
                        echo '<div class="notice notice-success is-dismissible"><p>' . $success_message . '</p></div>';
                    } else {
                         // Fallback para um resultado inesperado (nem WP_Error, nem ID de post válido).
                         echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Ocorreu um problema desconhecido ao gerar ou salvar o artigo. Nenhum ID de post foi retornado.', 'autoblogpro' ) . '</p></div>';
                    }
                } elseif ( $niche_id === 0 && isset( $_POST['autobp_niche_id'] ) ) {
                    // Se o formulário foi submetido, mas dados essenciais como nicho ou palavras-chave estão faltando.
                     echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Por favor, selecione um nicho e forneça palavras-chave.', 'autoblogpro' ) . '</p></div>';
                }

            } else {
                // Nonce inválido: exibe mensagem de erro.
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Falha na verificação de segurança (nonce).', 'autoblogpro' ) . '</p></div>';
            }
        }

        // Recuperar Nichos (CPT 'niche') para popular o dropdown no formulário.
        $niche_posts = get_posts( array(
            'post_type' => 'niche',
            'numberposts' => -1,
            'orderby' => 'title', // Ordena os nichos por título.
            'order' => 'ASC',     // Ordem ascendente.
            'fields' => 'ids_titles', // Otimização para buscar apenas IDs e títulos dos posts.
        ) );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form method="post" action=""> <?php // Submete para a própria página ?>
                <?php wp_nonce_field( 'autobp_generate_articles_action', 'autobp_generate_articles_nonce' ); // Campo Nonce para segurança ?>

                <table class="form-table">
                    <?php // Campo para selecionar o Nicho ?>
                    <tr valign="top">
                        <th scope="row">
                            <label for="autobp_niche_id"><?php esc_html_e( 'Selecione o Nicho', 'autoblogpro' ); ?></label>
                        </th>
                        <td>
                            <select name="autobp_niche_id" id="autobp_niche_id" class="postform" required>
                                <option value=""><?php esc_html_e( '-- Selecione um Nicho --', 'autoblogpro' ); ?></option>
                                <?php if ( ! empty( $niche_posts ) ) : ?>
                                    <?php foreach ( $niche_posts as $niche_post ) : ?>
                                        <option value="<?php echo esc_attr( $niche_post->ID ); ?>">
                                            <?php echo esc_html( $niche_post->post_title ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <option value="" disabled><?php esc_html_e( 'Nenhum nicho encontrado. Por favor, crie um nicho primeiro.', 'autoblogpro' ); ?></option>
                                <?php endif; ?>
                            </select>
                             <p class="description"><?php esc_html_e( 'Selecione o nicho para o qual o artigo será gerado. As categorias associadas a este nicho serão aplicadas ao novo artigo.', 'autoblogpro' ); ?></p>
                        </td>
                    </tr>
                    <?php // Campo para definir o número de artigos (atualmente, apenas 1 é gerado por vez) ?>
                    <tr valign="top">
                        <th scope="row">
                            <label for="autobp_num_articles"><?php esc_html_e( 'Número de Artigos a Gerar', 'autoblogpro' ); ?></label>
                        </th>
                        <td>
                            <input type="number" name="autobp_num_articles" id="autobp_num_articles" value="1" min="1" class="small-text">
                            <p class="description"><?php esc_html_e( 'Nota: Atualmente, o sistema gera 1 artigo por vez, independentemente deste valor.', 'autoblogpro' ); ?></p>
                        </td>
                    </tr>
                    <?php // Campo para Palavras-chave Alvo ?>
                    <tr valign="top">
                        <th scope="row">
                            <label for="autobp_target_keywords"><?php esc_html_e( 'Palavras-chave Alvo (separadas por vírgula)', 'autoblogpro' ); ?></label>
                        </th>
                        <td>
                            <input type="text" name="autobp_target_keywords" id="autobp_target_keywords" class="large-text" required>
                             <p class="description"><?php esc_html_e( 'Ex: marketing digital, SEO, criação de conteúdo. Campo obrigatório.', 'autoblogpro' ); ?></p>
                        </td>
                    </tr>
                    <?php // Campo para Tamanho Médio Estimado ?>
                    <tr valign="top">
                        <th scope="row">
                            <label for="autobp_article_length"><?php esc_html_e( 'Tamanho Médio Estimado (palavras)', 'autoblogpro' ); ?></label>
                        </th>
                        <td>
                            <input type="number" name="autobp_article_length" id="autobp_article_length" value="500" min="100" step="50" class="small-text">
                            <p class="description"><?php esc_html_e( 'Aproximadamente quantas palavras o artigo deve ter.', 'autoblogpro' ); ?></p>
                        </td>
                    </tr>
                    <?php // Campo para Tom de Voz ?>
                    <tr valign="top">
                        <th scope="row">
                            <label for="autobp_tone_of_voice"><?php esc_html_e( 'Tom de Voz', 'autoblogpro' ); ?></label>
                        </th>
                        <td>
                            <select name="autobp_tone_of_voice" id="autobp_tone_of_voice">
                                <option value="neutro" selected><?php esc_html_e( 'Neutro', 'autoblogpro' ); ?></option>
                                <option value="formal"><?php esc_html_e( 'Formal', 'autoblogpro' ); ?></option>
                                <option value="informal"><?php esc_html_e( 'Informal', 'autoblogpro' ); ?></option>
                                <option value="persuasivo"><?php esc_html_e( 'Persuasivo', 'autoblogpro' ); ?></option>
                                <option value="criativo"><?php esc_html_e( 'Criativo', 'autoblogpro' ); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Define a formalidade e o sentimento geral do texto.', 'autoblogpro' ); ?></p>
                        </td>
                    </tr>
                    <?php // Campo para Estilo de Escrita ?>
                    <tr valign="top">
                        <th scope="row">
                            <label for="autobp_writing_style"><?php esc_html_e( 'Estilo de Escrita', 'autoblogpro' ); ?></label>
                        </th>
                        <td>
                            <select name="autobp_writing_style" id="autobp_writing_style">
                                <option value="informativo" selected><?php esc_html_e( 'Informativo', 'autoblogpro' ); ?></option>
                                <option value="narrativo"><?php esc_html_e( 'Narrativo', 'autoblogpro' ); ?></option>
                                <option value="opinativo"><?php esc_html_e( 'Opinativo', 'autoblogpro' ); ?></option>
                                <option value="tutorial"><?php esc_html_e( 'Tutorial', 'autoblogpro' ); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Como o conteúdo será apresentado (ex: um guia, uma história, uma opinião).', 'autoblogpro' ); ?></p>
                        </td>
                    </tr>
                    <?php // Campo para Criatividade (Temperatura da API) ?>
                    <tr valign="top">
                        <th scope="row">
                            <label for="autobp_creativity"><?php esc_html_e( 'Nível de Criatividade (0.1 - 1.0)', 'autoblogpro' ); ?></label>
                        </th>
                        <td>
                            <input type="range" name="autobp_creativity" id="autobp_creativity" min="0.1" max="1.0" step="0.1" value="0.7" oninput="this.nextElementSibling.value = this.value">
                            <output>0.7</output> <?php // Exibe o valor do range ?>
                            <p class="description"><?php esc_html_e( 'Valores mais altos significam resultados mais criativos e aleatórios (temperatura da API).', 'autoblogpro' ); ?></p>
                        </td>
                    </tr>
                    <?php // Campo para Palavras-chave Negativas ?>
                     <tr valign="top">
                        <th scope="row">
                            <label for="autobp_negative_keywords"><?php esc_html_e( 'Palavras-chave Negativas (separadas por vírgula)', 'autoblogpro' ); ?></label>
                        </th>
                        <td>
                            <input type="text" name="autobp_negative_keywords" id="autobp_negative_keywords" class="large-text">
                             <p class="description"><?php esc_html_e( 'Termos ou tópicos que a IA deve evitar estritamente. Ex: política, controvérsia', 'autoblogpro' ); ?></p>
                        </td>
                    </tr>
                    <?php // Campo para Idioma ?>
                    <tr valign="top">
                        <th scope="row">
                            <label for="autobp_language"><?php esc_html_e( 'Idioma de Geração (ex: pt-BR, en-US)', 'autoblogpro' ); ?></label>
                        </th>
                        <td>
                            <input type="text" name="autobp_language" id="autobp_language" value="pt-BR" class="regular-text">
                            <p class="description"><?php esc_html_e( 'Define o idioma em que o artigo será escrito.', 'autoblogpro' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Gerar Artigos', 'autoblogpro' ) ); ?>
            </form>
        </div>
        <?php
    }
}

?>
