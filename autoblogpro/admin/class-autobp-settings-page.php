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

        add_submenu_page(
            self::$main_menu_slug,
            __( 'Biblioteca de Artigos - AutoBlogPro', 'autoblogpro' ),
            __( 'Biblioteca de Artigos', 'autoblogpro' ),
            'manage_options', // Ou 'edit_posts' se preferir
            'autobp-article-library',
            array( __CLASS__, 'render_article_library_page' )
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
     * como nicho, número de artigos, palavras-chave, tamanho do artigo, tom de voz,
     * estilo, criatividade, palavras-chave negativas, idioma e número de seções H2.
     *
     * Ao submeter o formulário, os dados são coletados e sanitizados.
     * Para cada artigo solicitado, instancia `AutoBP_Content_Generator` e chama seu método `generate()`.
     * O feedback (sucesso ou erro) para cada artigo gerado é exibido na página.
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
                // Campo: Número de Artigos a Gerar
                $num_articles_to_generate = isset( $_POST['autobp_num_articles'] ) ? intval( $_POST['autobp_num_articles'] ) : 1;
                if ($num_articles_to_generate < 1) $num_articles_to_generate = 1; // Garante pelo menos 1.

                $target_keywords = isset( $_POST['autobp_target_keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['autobp_target_keywords'] ) ) : '';

                // Coleta e sanitização dos campos de personalização.
                // Campo: Tamanho Médio Estimado (palavras)
                $article_length = isset( $_POST['autobp_article_length'] ) ? intval( $_POST['autobp_article_length'] ) : 500;
                if ($article_length < 50) $article_length = 50; // Validação mínima.

                // Campo: Tom de Voz
                $tone_of_voice = isset( $_POST['autobp_tone_of_voice'] ) ? sanitize_key( $_POST['autobp_tone_of_voice'] ) : 'neutro';

                // Campo: Estilo de Escrita
                $writing_style = isset( $_POST['autobp_writing_style'] ) ? sanitize_key( $_POST['autobp_writing_style'] ) : 'informativo';

                // Campo: Criatividade (Nível de Originalidade)
                $creativity = isset( $_POST['autobp_creativity'] ) ? floatval( $_POST['autobp_creativity'] ) : 0.7;
                if ($creativity < 0.1) $creativity = 0.1; // Validação de range.
                if ($creativity > 1.0) $creativity = 1.0; // O formulário limita a 1.0, mas a API aceita até 2.0.
                                                        // Mantemos a consistência com o formulário aqui.

                // Campo: Palavras-chave Negativas
                $negative_keywords = isset( $_POST['autobp_negative_keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['autobp_negative_keywords'] ) ) : '';

                // Campo: Idioma
                $language = isset( $_POST['autobp_language'] ) ? sanitize_text_field( wp_unslash( $_POST['autobp_language'] ) ) : 'pt-BR';

                // Campo: Número de Seções Principais (H2)
                $num_h2_sections = isset( $_POST['autobp_num_h2_sections'] ) ? intval( $_POST['autobp_num_h2_sections'] ) : 4;
                if ( $num_h2_sections < 1 ) $num_h2_sections = 1; // Validação de range.
                if ( $num_h2_sections > 10 ) $num_h2_sections = 10;

                // Recupera a chave da API OpenAI salva nas configurações.
                $api_key = get_option( self::$api_key_option_name, '' );

                // Carrega a classe AutoBP_Content_Generator se ainda não estiver carregada.
                if ( ! class_exists( 'AutoBP_Content_Generator' ) ) {
                    if ( file_exists( AUTOBP_PLUGIN_DIR . 'includes/class-autobp-content-generator.php' ) ) {
                        require_once AUTOBP_PLUGIN_DIR . 'includes/class-autobp-content-generator.php';
                    } else {
                        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Erro crítico: Arquivo do gerador de conteúdo não encontrado.', 'autoblogpro' ) . '</p></div>';
                        $niche_id = 0; // Previne a execução do loop de geração.
                    }
                }

                // Apenas prossegue se a classe AutoBP_Content_Generator existir e os dados básicos (nicho, palavras-chave, num_articles_to_generate) forem válidos.
                if ( class_exists('AutoBP_Content_Generator') && $niche_id > 0 && !empty($target_keywords) && $num_articles_to_generate > 0 ) {

                    $results = array(); // Array para armazenar os resultados de cada geração (ID do post ou WP_Error).

                    // Instancia o gerador de conteúdo uma vez com os parâmetros comuns.
                    // O número total de artigos no lote ($num_articles_to_generate) é passado para o construtor
                    // para que a classe `ContentGenerator` possa usá-lo, por exemplo, para formatar títulos como "(#X/Y)".
                    $generator = new AutoBP_Content_Generator(
                        $api_key,
                        $niche_id,
                        $num_articles_to_generate,
                        $target_keywords,
                        $article_length,
                        $tone_of_voice,
                        $writing_style,
                        $creativity,
                        $negative_keywords,
                        $language,
                        $num_h2_sections
                    );

                    // Loop para gerar o número de artigos especificado.
                    for ( $i = 0; $i < $num_articles_to_generate; $i++ ) {
                        // O método generate() aceita o índice do artigo atual (1-indexado) para diferenciação.
                        $result = $generator->generate( $i + 1 );
                        $results[] = $result;
                    }

                    // Exibe os resultados de cada tentativa de geração.
                    echo '<h4>' . esc_html__( 'Resultados da Geração:', 'autoblogpro' ) . '</h4>';
                    echo '<ul style="list-style-type: disc; padding-left: 20px;">';

                    foreach ( $results as $index => $individual_result ) {
                        $article_num_display = $index + 1; // Número do artigo para exibição (1-indexado).
                        if ( is_wp_error( $individual_result ) ) {
                            $error_message = $individual_result->get_error_message();
                            $error_code = $individual_result->get_error_code();
                            // translators: %1$d: Número do artigo na sequência. %2$s: Código do erro. %3$s: Mensagem de erro.
                            echo '<li>' . sprintf( esc_html__( 'Artigo %1$d: Falha - %2$s: %3$s', 'autoblogpro' ), $article_num_display, esc_html($error_code), esc_html($error_message) ) . '</li>';
                        } elseif ( is_numeric( $individual_result ) && $individual_result > 0 ) {
                            $post_id = $individual_result;
                            $edit_link = get_edit_post_link( $post_id );
                            $title = get_the_title( $post_id );
                            $success_message_text = sprintf(
                                // translators: %1$s: Título do post gerado.
                                __( 'Artigo "%1$s" gerado e salvo como rascunho!', 'autoblogpro' ),
                                esc_html( $title )
                            );
                            // translators: %1$s: Texto de sucesso. %2$s Link para editar o post. %3$s Texto do link.
                            $success_message_html = sprintf(
                                '%1$s <a href="%2$s" target="_blank">%3$s</a>.',
                                $success_message_text,
                                esc_url( $edit_link ),
                                esc_html__( 'Editar rascunho', 'autoblogpro')
                            );
                            echo '<li>' . sprintf( esc_html__( 'Artigo %d: ', 'autoblogpro' ), $article_num_display ) . wp_kses_post( $success_message_html ) . '</li>';
                        } else {
                            // translators: %d: Número do artigo na sequência.
                            echo '<li>' . sprintf( esc_html__( 'Artigo %d: Resultado inesperado ou falha não especificada na geração.', 'autoblogpro' ), $article_num_display ) . '</li>';
                        }
                    }
                    echo '</ul>';

                } elseif ( ($niche_id === 0 || empty($target_keywords) || $num_articles_to_generate <= 0) && isset($_POST['autobp_generate_articles_nonce']) ) {
                    // Se o formulário foi submetido, mas dados essenciais como nicho, palavras-chave ou $num_articles_to_generate inválido.
                     echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Por favor, selecione um nicho, forneça palavras-chave e defina um número válido de artigos (pelo menos 1).', 'autoblogpro' ) . '</p></div>';
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
                    <?php // Campo para Número de Seções H2 ?>
                    <tr valign="top">
                        <th scope="row">
                            <label for="autobp_num_h2_sections"><?php esc_html_e( 'Número de Seções Principais (H2)', 'autoblogpro' ); ?></label>
                        </th>
                        <td>
                            <input type="number" name="autobp_num_h2_sections" id="autobp_num_h2_sections" value="4" min="1" max="10" step="1" class="small-text">
                            <p class="description"><?php esc_html_e( 'Define quantas seções principais (com subtítulos H2) o artigo deve ter. (Recomendado: 3-7)', 'autoblogpro' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Gerar Artigos', 'autoblogpro' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renderiza a página da Biblioteca de Artigos.
     *
     * Busca e exibe uma lista de todos os artigos gerados pelo plugin AutoBlogPro.
     *
     * Os artigos são identificados pelo metadado `_autobp_generated_from_niche_id`.
     * A lista é apresentada em uma tabela no estilo padrão do WordPress, com colunas para
     * título do artigo (com ações inline), nicho de origem, data de criação, status e
     * ações explícitas (Editar, Ver, Publicar, Lixeira).
     * Também lida com a exibição de mensagens de feedback passadas por parâmetro de URL
     * (ex: após uma ação de publicação).
     *
     * @since 0.1.0
     * @access public
     * @static
     */
    public static function render_article_library_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Biblioteca de Artigos Gerados', 'autoblogpro' ) . '</h1>';

        // Exibe mensagens de feedback (ex: após publicar um post via admin-post.php).
        // As mensagens são passadas via parâmetros de URL.
        if ( isset( $_GET['autobp_message'] ) && isset( $_GET['post_id'] ) ) {
            $message_type = sanitize_key( $_GET['autobp_message'] ); // Tipo de mensagem (ex: 'post_published').
            $post_id_feedback = intval( $_GET['post_id'] ); // ID do post relacionado à mensagem.
            $post_title_feedback = $post_id_feedback ? get_the_title( $post_id_feedback ) : ''; // Título para a mensagem.

            if ( $message_type === 'post_published' && $post_title_feedback ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( 'Post "%s" publicado com sucesso!', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'publish_failed' && $post_title_feedback ) {
                 echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'Falha ao publicar o post "%s".', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'publish_permission_denied' ) {
                 echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Você não tem permissão para publicar o post selecionado.', 'autoblogpro' ) . '</p></div>';
            } elseif ( $message_type === 'invalid_post_id' ) {
                 echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'ID do post inválido para a ação de publicação.', 'autoblogpro' ) . '</p></div>';
            }
        }

        // Argumentos para WP_Query para buscar os posts gerados pelo plugin.
        $args = array(
            'post_type'      => 'post',
            'post_status'    => array('publish', 'draft', 'pending', 'future', 'private'),
            'meta_key'       => '_autobp_generated_from_niche_id',
            'posts_per_page' => -1, // Mostrar todos por enquanto
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $generated_posts_query = new WP_Query( $args );

        if ( ! $generated_posts_query->have_posts() ) {
            echo '<p>' . esc_html__( 'Nenhum artigo gerado encontrado.', 'autoblogpro' ) . '</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped posts">';
            echo '<thead><tr>';
            echo '<th scope="col" id="title" class="manage-column column-title column-primary">' . esc_html__( 'Título do Artigo', 'autoblogpro' ) . '</th>';
            echo '<th scope="col" id="niche" class="manage-column column-niche">' . esc_html__( 'Nicho de Origem', 'autoblogpro' ) . '</th>';
            echo '<th scope="col" id="date" class="manage-column column-date">' . esc_html__( 'Data de Criação', 'autoblogpro' ) . '</th>';
            echo '<th scope="col" id="status" class="manage-column column-status">' . esc_html__( 'Status', 'autoblogpro' ) . '</th>';
            echo '<th scope="col" id="actions" class="manage-column column-actions">' . esc_html__( 'Ações', 'autoblogpro' ) . '</th>';
            echo '</tr></thead>';
            echo '<tbody id="the-list">';

            while ( $generated_posts_query->have_posts() ) {
                $generated_posts_query->the_post(); // Configura dados globais do post para funções como get_the_title() etc.

                $post_id = get_the_ID();
                $title = get_the_title();
                $status_object = get_post_status_object( get_post_status( $post_id ) );
                $status_label = $status_object ? $status_object->label : get_post_status( $post_id );
                $date = get_the_date( '', $post_id ); // Usa o formato de data padrão do WordPress

                $niche_id = get_post_meta( $post_id, '_autobp_generated_from_niche_id', true );
                $niche_title = '';
                if ($niche_id) {
                    $niche_post = get_post($niche_id);
                    if ($niche_post) {
                        $niche_title = get_the_title($niche_id);
                    } else {
                        // translators: %d: ID do nicho que foi deletado ou não encontrado.
                        $niche_title = sprintf(esc_html__('ID do Nicho: %d (Nicho não encontrado/deletado)', 'autoblogpro'), $niche_id);
                    }
                } else {
                    $niche_title = __('N/A', 'autoblogpro');
                }

                echo '<tr>';
                echo '<td class="title column-title has-row-actions column-primary page-title" data-colname="' . esc_attr__( 'Título do Artigo', 'autoblogpro' ) . '">';
                echo '<strong><a class="row-title" href="' . esc_url( get_edit_post_link( $post_id ) ) . '" target="_blank">' . esc_html( $title ) . '</a></strong>';
                // Ações inline (visíveis no hover em telas maiores)
                echo '<div class="row-actions">';
                echo '<span class="edit"><a href="' . esc_url( get_edit_post_link( $post_id ) ) . '" target="_blank">' . esc_html__( 'Editar', 'autoblogpro' ) . '</a></span>';
                if ( get_post_status( $post_id ) === 'publish' || current_user_can('edit_post', $post_id) ) {
                     echo ' | <span class="view"><a href="' . esc_url( get_permalink( $post_id ) ) . '" target="_blank">' . esc_html__( 'Ver', 'autoblogpro' ) . '</a></span>';
                }
                 // Adicionar link de Lixeira (Trash)
                if ( current_user_can( 'delete_post', $post_id ) ) {
                    echo ' | <span class="trash"><a href="' . esc_url( get_delete_post_link( $post_id ) ) . '" class="submitdelete" aria-label="' . esc_attr__( 'Mover para a lixeira', 'autoblogpro') . '">' . esc_html__( 'Lixeira', 'autoblogpro' ) . '</a></span>';
                }
                // Adicionar link de Publicar se for rascunho
                if ( get_post_status( $post_id ) === 'draft' && current_user_can( 'publish_post', $post_id ) ) {
                    $publish_nonce = wp_create_nonce( 'autobp_publish_post_' . $post_id );
                    $publish_link = admin_url( 'admin-post.php?action=autobp_publish_post&post_id=' . $post_id . '&_wpnonce=' . $publish_nonce );
                    echo ' | <span class="publish"><a href="' . esc_url( $publish_link ) . '" style="color: green;" aria-label="' . esc_attr__('Publicar este rascunho', 'autoblogpro') . '">' . esc_html__( 'Publicar', 'autoblogpro' ) . '</a></span>';
                }
                echo '</div>';
                echo '</td>';
                echo '<td data-colname="' . esc_attr__( 'Nicho de Origem', 'autoblogpro' ) . '">' . esc_html( $niche_title ) . '</td>';
                echo '<td data-colname="' . esc_attr__( 'Data de Criação', 'autoblogpro' ) . '">' . esc_html( $date ) . '</td>';
                echo '<td data-colname="' . esc_attr__( 'Status', 'autoblogpro' ) . '">' . esc_html( $status_label ) . '</td>';

                // Coluna de Ações explícita (para consistência, embora as ações inline sejam comuns)
                // Os links aqui podem replicar os da coluna de título ou ter ações adicionais se necessário.
                // Por ora, manteremos simples, pois as ações principais já estão no hover do título.
                echo '<td data-colname="' . esc_attr__( 'Ações', 'autoblogpro' ) . '">';
                echo '<a href="' . esc_url( get_edit_post_link( $post_id ) ) . '" target="_blank" class="button button-small">' . esc_html__( 'Editar', 'autoblogpro' ) . '</a> ';
                if ( get_post_status( $post_id ) === 'publish' || current_user_can('edit_post', $post_id) ) {
                     echo '<a href="' . esc_url( get_permalink( $post_id ) ) . '" target="_blank" class="button button-small">' . esc_html__( 'Ver', 'autoblogpro' ) . '</a> ';
                }
                 if ( get_post_status( $post_id ) === 'draft' && current_user_can( 'publish_post', $post_id ) ) {
                    $publish_nonce_col = wp_create_nonce( 'autobp_publish_post_' . $post_id ); // Novo nonce para evitar conflito se o mesmo post listado várias vezes (improvável aqui)
                    $publish_link_col = admin_url( 'admin-post.php?action=autobp_publish_post&post_id=' . $post_id . '&_wpnonce=' . $publish_nonce_col );
                    echo '<a href="' . esc_url( $publish_link_col ) . '" class="button button-small button-primary" style="color: white; background-color: green; border-color: darkgreen;">' . esc_html__( 'Publicar', 'autoblogpro' ) . '</a>';
                }
                echo '</td>';
                echo '</tr>';
            }
            wp_reset_postdata(); // Restaura dados globais do post
            echo '</tbody></table>';
        }
        echo '</div>'; // Fim do .wrap
    }
}

?>
