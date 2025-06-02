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

        add_submenu_page(
            self::$main_menu_slug,
            __( 'Logs do Sistema - AutoBlogPro', 'autoblogpro' ),
            __( 'Logs do Sistema', 'autoblogpro' ),
            'manage_options', // Ou uma capacidade personalizada como 'view_autobp_logs'
            'autobp-system-logs',
            array( __CLASS__, 'render_system_logs_page' )
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
     * - Registra a opção para a chave da API OpenAI (`autobp_openai_api_key`).
     * - Registra as opções para as credenciais da API Copyscape:
     *   - Nome de Usuário (`autobp_copyscape_username`).
     *   - Chave da API (`autobp_copyscape_api_key`).
     * - Registra a opção para a chave da API Pexels (`autobp_pexels_api_key`). (Desde 0.2.0)
     * - Registra as opções para integração com plugins de SEO: (Desde 0.2.0)
     *   - Plugin de SEO Ativo (`autobp_active_seo_plugin`: 'none', 'yoast', 'rankmath').
     *   - Habilitar Preenchimento Automático (`autobp_seo_plugin_fill_fields`: 0 ou 1).
     * Define as seções de configurações ("API OpenAI", "API Copyscape", "API Pexels", "Integração com Plugins de SEO")
     * e os campos correspondentes na página de configurações `autobp-settings`.
     * Utiliza callbacks de renderização para os campos e textos das seções.
     * Um callback de sanitização genérico (`sanitize_api_key` para strings, `sanitize_key`, `intval`)
     * é usado para os campos.
     *
     * @since 0.1.0 (Configurações Pexels e Integração SEO adicionadas na 0.2.0)
     * @access public
     * @static
     */
    public static function register_settings() {
        // Opção para a chave da API OpenAI
        register_setting(
            self::$option_group,                          // Nome do grupo de opções.
            self::$api_key_option_name,                   // Nome da opção (autobp_openai_api_key).
            array( __CLASS__, 'sanitize_api_key' )        // Callback para sanitizar o input.
        );

        // Seção para Configurações da API OpenAI
        add_settings_section(
            'autobp_openai_section',                      // ID da seção.
            __( 'Configurações da API OpenAI', 'autoblogpro' ), // Título da seção.
            null,                                         // Sem callback de descrição de seção.
            self::$settings_page_slug                     // Página onde a seção será exibida.
        );

        // Campo para a Chave da API OpenAI
        add_settings_field(
            'autobp_openai_api_key_field',                // ID do campo.
            __( 'Chave da API OpenAI', 'autoblogpro' ),   // Título do campo.
            array( __CLASS__, 'render_api_key_field' ),   // Callback para renderizar o HTML do campo.
            self::$settings_page_slug,                    // Página.
            'autobp_openai_section'                       // Seção à qual pertence.
        );

        // --- Configurações da API Copyscape ---

        // Seção para Configurações da API Copyscape
        add_settings_section(
            'autobp_copyscape_section',                                 // ID da seção.
            __( 'Configurações da API Copyscape', 'autoblogpro' ),      // Título.
            array( __CLASS__, 'render_copyscape_section_text' ),      // Callback para texto introdutório.
            self::$settings_page_slug                                   // Página.
        );

        // Campo para Nome de Usuário Copyscape
        add_settings_field(
            'autobp_copyscape_username',                                // ID do campo.
            __( 'Nome de Usuário Copyscape', 'autoblogpro' ),           // Título.
            array( __CLASS__, 'render_copyscape_username_field' ),    // Callback de renderização.
            self::$settings_page_slug,                                  // Página.
            'autobp_copyscape_section'                                  // Seção.
        );
        register_setting( 
            self::$option_group,                                        // Grupo de opções.
            'autobp_copyscape_username',                                // Nome da opção.
            array( __CLASS__, 'sanitize_api_key' )                      // Callback de sanitização (reutilizado).
        );

        // Campo para Chave da API Copyscape
        add_settings_field(
            'autobp_copyscape_api_key',                                 // ID do campo.
            __( 'Chave da API Copyscape', 'autoblogpro' ),              // Título.
            array( __CLASS__, 'render_copyscape_api_key_field' ),     // Callback de renderização.
            self::$settings_page_slug,                                  // Página.
            'autobp_copyscape_section'                                  // Seção.
        );
        register_setting( 
            self::$option_group,                                        // Grupo de opções.
            'autobp_copyscape_api_key',                                 // Nome da opção.
            array( __CLASS__, 'sanitize_api_key' )                      // Callback de sanitização (reutilizado).
        );

        // --- Configurações da API Pexels ---
        add_settings_section(
            'autobp_pexels_section',
            __( 'Configurações da API Pexels', 'autoblogpro' ),
            array( __CLASS__, 'render_pexels_section_text' ),
            self::$settings_page_slug 
        );

        add_settings_field(
            'autobp_pexels_api_key',
            __( 'Chave da API Pexels', 'autoblogpro' ),
            array( __CLASS__, 'render_pexels_api_key_field' ),
            self::$settings_page_slug,
            'autobp_pexels_section'
        );
        register_setting( 
            self::$option_group, 
            'autobp_pexels_api_key', 
            array( __CLASS__, 'sanitize_api_key' ) 
        );

        // --- Integração com Plugins de SEO ---
        add_settings_section(
            'autobp_seo_plugins_integration_section',
            __( 'Integração com Plugins de SEO', 'autoblogpro' ),
            array( __CLASS__, 'render_seo_plugins_integration_section_text' ),
            self::$settings_page_slug // slug da página de configurações
        );

        add_settings_field(
            'autobp_active_seo_plugin',
            __( 'Plugin de SEO Ativo', 'autoblogpro' ),
            array( __CLASS__, 'render_active_seo_plugin_field' ),
            self::$settings_page_slug,
            'autobp_seo_plugins_integration_section'
        );
        register_setting( 'autobp_settings_group', 'autobp_active_seo_plugin', 'sanitize_key' );

        add_settings_field(
            'autobp_seo_plugin_fill_fields',
            __( 'Preencher Campos Automaticamente', 'autoblogpro' ),
            array( __CLASS__, 'render_seo_plugin_fill_fields_field' ),
            self::$settings_page_slug, // Corrigido para o slug correto da página
            'autobp_seo_plugins_integration_section'
        );
        register_setting( 'autobp_settings_group', 'autobp_seo_plugin_fill_fields', 'intval' ); // 0 ou 1

        // --- Monitoramento de Uso da API OpenAI ---
        add_settings_section(
            'autobp_openai_usage_section',
            __( 'Monitoramento de Uso da API OpenAI (Estimativa)', 'autoblogpro' ),
            array( __CLASS__, 'render_openai_usage_section_text' ),
            self::$settings_page_slug // slug da página de configurações
        );
        // Não há campos registráveis aqui, apenas exibição e um botão de ação.
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
            <?php 
            // Exibir mensagens de feedback
            if ( isset( $_GET['autobp_message'] ) ) {
                $message_code = sanitize_key( $_GET['autobp_message'] );
                if ( $message_code === 'tokens_reset' ) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'A contagem de tokens OpenAI foi zerada com sucesso.', 'autoblogpro' ) . '</p></div>';
                }
                // Adicionar outros cases de mensagens aqui se necessário
            }
            settings_errors(); // Exibe mensagens de erro/sucesso para a página de config (da API de Settings)
            ?>
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
     * Renderiza o texto introdutório para a seção de configurações da API Pexels.
     *
     * @since 0.2.0
     * @access public
     * @static
     */
    public static function render_pexels_section_text() {
        echo '<p>' . esc_html__( 'Insira sua chave da API Pexels para permitir a busca e integração de imagens de alta qualidade em seus artigos.', 'autoblogpro' ) . '</p>';
        echo '<p>' . sprintf( 
                wp_kses( 
                    __( 'Você pode obter uma chave de API gratuita no site da <a href="%s" target="_blank">Pexels</a>.', 'autoblogpro' ),
                    array( 'a' => array( 'href' => array(), 'target' => array() ) )
                ),
                esc_url( 'https://www.pexels.com/api/' )
            ) . '</p>';
    }

    /**
     * Renderiza o campo de input HTML para a chave da API Pexels.
     *
     * @since 0.2.0
     * @access public
     * @static
     */
    public static function render_pexels_api_key_field() {
        $api_key = get_option( 'autobp_pexels_api_key', '' );
        ?>
        <input type="text" name="autobp_pexels_api_key" id="autobp_pexels_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text">
        <p class="description"><?php esc_html_e( 'Sua chave da API Pexels.', 'autoblogpro' ); ?></p>
        <?php
    }

    /**
     * Renderiza o texto introdutório para a seção de integração com plugins de SEO.
     * @since 0.2.0
     */
    public static function render_seo_plugins_integration_section_text() {
        echo '<p>' . esc_html__( 'Configure como o AutoBlogPro interage com seu plugin de SEO favorito (Yoast SEO ou Rank Math).', 'autoblogpro' ) . '</p>';
    }

    /**
     * Renderiza o campo dropdown para selecionar o plugin de SEO ativo.
     * @since 0.2.0
     */
    public static function render_active_seo_plugin_field() {
        $current_value = get_option( 'autobp_active_seo_plugin', 'none' );
        ?>
        <select name="autobp_active_seo_plugin" id="autobp_active_seo_plugin">
            <option value="none" <?php selected( $current_value, 'none' ); ?>><?php esc_html_e('Nenhum / Desabilitado', 'autoblogpro'); ?></option>
            <option value="yoast" <?php selected( $current_value, 'yoast' ); ?>><?php esc_html_e('Yoast SEO', 'autoblogpro'); ?></option>
            <option value="rankmath" <?php selected( $current_value, 'rankmath' ); ?>><?php esc_html_e('Rank Math', 'autoblogpro'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Selecione o plugin de SEO que você utiliza. Isso permitirá que o AutoBlogPro tente preencher campos como palavra-chave foco e meta descrição.', 'autoblogpro'); ?></p>
        <?php
    }

    /**
     * Renderiza o campo checkbox para habilitar/desabilitar o preenchimento automático dos campos de SEO.
     * @since 0.2.0
     */
    public static function render_seo_plugin_fill_fields_field() {
        $current_value = get_option( 'autobp_seo_plugin_fill_fields', 0 );
        ?>
        <label>
            <input type="checkbox" name="autobp_seo_plugin_fill_fields" value="1" <?php checked( $current_value, 1 ); ?>>
            <?php esc_html_e('Habilitar preenchimento automático da palavra-chave foco e meta descrição para o plugin de SEO selecionado.', 'autoblogpro'); ?>
        </label>
        <p class="description"><?php esc_html_e('Se marcado, o AutoBlogPro tentará preencher automaticamente os campos relevantes do plugin de SEO após a geração do conteúdo.', 'autoblogpro'); ?></p>
        <?php
    }

    /**
     * Renderiza o texto e o botão para a seção de Monitoramento de Uso da API OpenAI.
     * @since 0.2.0
     */
    public static function render_openai_usage_section_text() {
        $total_tokens = get_option( 'autobp_openai_total_tokens_used', 0 );
        echo '<p>' . sprintf( esc_html__( 'Total estimado de tokens OpenAI usados desde a última reinicialização: %s', 'autoblogpro' ), '<strong>' . number_format_i18n( $total_tokens ) . '</strong>' ) . '</p>';
        echo '<p class="description">' . esc_html__( 'Nota: Esta é uma estimativa baseada nas respostas da API. Para o uso exato e custos, consulte seu painel da OpenAI.', 'autoblogpro' ) . '</p>';
        
        // Formulário para o botão de zerar
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:10px; display:inline-block;">'; // display:inline-block para que não quebre a linha se houver outro form ao lado
        echo '<input type="hidden" name="action" value="autobp_reset_openai_token_count">';
        wp_nonce_field( 'autobp_reset_openai_token_count_action', 'autobp_reset_openai_token_count_nonce' );
        submit_button( __( 'Zerar Contagem de Tokens', 'autoblogpro' ), 'delete small', 'reset_tokens_button', false ); // 'delete' class para cor vermelha, 'small' para tamanho
        echo '</form>';
    }
    
    /**
     * Sanitiza o valor da chave da API OpenAI antes de salvá-la no banco de dados.
     *
     * Utiliza `sanitize_text_field` para remover tags HTML e outros elementos potencialmente perigosos.
     * Este método é reutilizado para sanitizar outras chaves de API que são strings simples.
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
     * Esta página utiliza um sistema de abas ("Modo Automático" e "Modo URL Base")
     * para organizar os formulários de geração.
     * A aba ativa é determinada pelo parâmetro de URL `tab` (ex: `&tab=automatic_mode`).
     * O PHP lê este parâmetro para definir qual aba deve ser exibida como ativa no carregamento.
     *
     * O conteúdo de cada aba é renderizado por métodos privados dedicados:
     * - `self::render_automatic_mode_form_content()` para o "Modo Automático".
     * - `self::render_url_mode_form_content()` para o "Modo URL Base".
     *
     * A página inclui CSS inline básico para a funcionalidade das abas (mostrar/esconder conteúdo)
     * e JavaScript (jQuery) não é estritamente necessário para a troca de abas com recarregamento
     * da página via links, mas poderia ser usado para uma experiência de usuário mais dinâmica
     * (troca de abas sem recarregar e atualizando a URL com `history.pushState`).
     * Atualmente, a troca de abas é feita por links que recarregam a página com o parâmetro `tab` apropriado.
     *
     * O processamento dos formulários das abas é delegado a actions via `admin-post.php`:
     * - "Modo Automático" submete para `admin_post_autobp_generate_articles_auto`.
     * - "Modo URL Base" (extração e reescrita) submetem para `admin_post_autobp_process_url_base`
     *   e `admin_post_autobp_rewrite_url_content` respectivamente.
     * O feedback dessas actions (mensagens de sucesso/erro, dados extraídos) é exibido
     * dentro do conteúdo da aba correspondente, geralmente via query args na URL de redirecionamento
     * ou transients.
     *
     * @since 0.1.0 (Refatorado para abas na versão 0.2.0)
     * @access public
     * @static
     */
    public static function render_generate_articles_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'automatic_mode';

        // O processamento dos formulários foi movido para admin-post.php handlers.
        // Mensagens de feedback são recuperadas e exibidas dentro dos métodos de renderização das abas.
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <?php
            // Exibir mensagens de feedback globais aqui, se necessário, ou dentro das abas.
            // Ex: if ( isset( $_GET['autobp_global_message'] ) ) { ... } 
            ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo esc_attr(self::$generate_page_slug); ?>&tab=automatic_mode" class="nav-tab <?php echo $active_tab === 'automatic_mode' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Modo Automático', 'autoblogpro' ); ?>
                </a>
                <a href="?page=<?php echo esc_attr(self::$generate_page_slug); ?>&tab=url_mode" class="nav-tab <?php echo $active_tab === 'url_mode' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Modo URL Base', 'autoblogpro' ); ?>
                </a>
            </h2>

            <div id="tab_automatic_mode_content" class="tab-content <?php echo $active_tab === 'automatic_mode' ? 'active' : ''; ?>" style="<?php echo $active_tab === 'automatic_mode' ? '' : 'display:none;'; ?>">
                <?php self::render_automatic_mode_form_content(); ?>
            </div>

            <div id="tab_url_mode_content" class="tab-content <?php echo $active_tab === 'url_mode' ? 'active' : ''; ?>" style="<?php echo $active_tab === 'url_mode' ? '' : 'display:none;'; ?>">
                <?php self::render_url_mode_form_content(); ?>
            </div>
        </div>
        <style type="text/css">
            /* .tab-content { display: none; } */ /* CSS é ajustado inline agora */
            .tab-content.active { display: block; margin-top: 20px;}
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Script simples para mostrar/esconder abas sem recarregar, se desejado no futuro.
                // Por enquanto, a navegação por link com parâmetro 'tab' já funciona.
                // Se for usar JS para troca de abas:
                // $('.nav-tab-wrapper a').on('click', function(e) {
                //     e.preventDefault();
                //     var $this = $(this);
                //     var newTab = $this.attr('href').split('tab=')[1];
                // 
                //     $('.nav-tab').removeClass('nav-tab-active');
                //     $this.addClass('nav-tab-active');
                // 
                //     $('.tab-content').hide(); // ou .removeClass('active')
                //     $('#tab_' + newTab + '_content').show(); // ou .addClass('active')
                // 
                //     // Atualizar URL sem recarregar
                //     var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?page=<?php echo esc_js(self::$generate_page_slug); ?>&tab=' + newTab;
                //     window.history.pushState({path:newUrl},'',newUrl);
                // });
            });
        </script>
        <?php
    }

    /**
     * Renderiza o conteúdo do formulário para o "Modo Automático" de geração de artigos.
     *
     * Este método é responsável por exibir o formulário completo para o modo de geração
     * automática de artigos. Inclui campos para:
     * - Seleção de Nicho (para herdar categorias).
     * - Número de artigos a gerar.
     * - Palavras-chave alvo.
     * - Configurações de personalização da IA (tamanho do artigo, tom de voz, estilo, criatividade,
     *   palavras-chave negativas, idioma, número de seções H2).
     * - Checkbox "Gerar Meta Descrição": Permite ao usuário optar pela geração automática
     *   de uma meta descrição para o(s) artigo(s) gerado(s).
     *
     * O formulário submete seus dados para a action `admin_post_autobp_generate_articles_auto`
     * através de `admin-post.php`. Um nonce (`autobp_generate_articles_auto_nonce`) é incluído
     * para segurança. Um campo oculto `tab=automatic_mode` é usado para garantir que, após o
     * processamento e redirecionamento, a aba correta seja exibida.
     *
     * Os resultados da geração (sucesso ou erro para cada artigo) são recuperados de um
     * transient (nomeado `autobp_generation_results_{user_id}`) e exibidos no topo desta
     * aba após o redirecionamento. Mensagens de erro específicas (ex: falha de nonce,
     * parâmetros ausentes) também são tratadas e exibidas com base em query args
     * (`autobp_auto_mode_message`).
     *
     * @since 0.2.0 (Refatorado de `render_generate_articles_page`)
     * @access private
     * @static
     */
    private static function render_automatic_mode_form_content() {
        // Exibir mensagens de feedback para o modo automático
        if ( isset( $_GET['autobp_auto_mode_message'] ) ) {
            $message_code = sanitize_key( $_GET['autobp_auto_mode_message'] );
            $messages = array(
                'nonce_failure' => array( 'type' => 'error', 'text' => __( 'Falha na verificação de segurança (nonce). Por favor, tente novamente.', 'autoblogpro' ) ),
                'params_missing' => array( 'type' => 'warning', 'text' => __( 'Por favor, selecione um nicho, forneça palavras-chave e defina um número válido de artigos.', 'autoblogpro' ) ),
                'api_key_missing' => array( 'type' => 'error', 'text' => __( 'A chave da API OpenAI não está configurada. Por favor, adicione-a na página de Configurações.', 'autoblogpro' ) ),
                'generator_missing' => array( 'type' => 'error', 'text' => __( 'Erro crítico: Arquivo do gerador de conteúdo não encontrado.', 'autoblogpro' ) ),
            );
            if ( isset( $messages[$message_code] ) ) {
                $message = $messages[$message_code];
                echo '<div class="notice notice-' . esc_attr( $message['type'] ) . ' is-dismissible"><p>' . esc_html( $message['text'] ) . '</p></div>';
            }
        }

        // Exibir resultados da geração (do transient)
        $user_id = get_current_user_id();
        $generation_results_transient = 'autobp_generation_results_' . $user_id;
        $results = get_transient( $generation_results_transient );

        if ( $results ) {
            echo '<h4>' . esc_html__( 'Resultados da Última Geração:', 'autoblogpro' ) . '</h4>';
            echo '<ul style="list-style-type: disc; padding-left: 20px; margin-bottom: 20px;">';
            foreach ( $results as $index => $individual_result ) {
                $article_num_display = $index + 1;
                if ( isset( $individual_result['error'] ) && $individual_result['error'] ) {
                    // translators: %1$d: Número do artigo. %2$s: Código do erro. %3$s: Mensagem de erro.
                    echo '<li>' . sprintf( esc_html__( 'Artigo %1$d: Falha - %2$s: %3$s', 'autoblogpro' ), $article_num_display, esc_html($individual_result['error_code']), esc_html($individual_result['message']) ) . '</li>';
                } elseif ( isset( $individual_result['success'] ) && $individual_result['success'] ) {
                    $success_message_html = sprintf(
                        // translators: %1$s: Título do post. %2$s: Link para editar. %3$s: Texto do link.
                        '%1$s <a href="%2$s" target="_blank">%3$s</a>.',
                        esc_html( $individual_result['message'] ),
                        esc_url( $individual_result['edit_link'] ),
                        esc_html__( 'Editar rascunho', 'autoblogpro')
                    );
                    // translators: %d: Número do artigo.
                    echo '<li>' . sprintf( esc_html__( 'Artigo %d: ', 'autoblogpro' ), $article_num_display ) . wp_kses_post( $success_message_html ) . '</li>';
                }
            }
            echo '</ul>';
            delete_transient( $generation_results_transient ); // Limpar o transient após exibir
        }
        
        // Recuperar Nichos (CPT 'niche') para popular o dropdown no formulário.
        $niche_posts = get_posts( array(
            'post_type' => 'niche',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids_titles',
        ) );
        ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="autobp_generate_articles_auto">
            <input type="hidden" name="tab" value="automatic_mode">
            <?php wp_nonce_field( 'autobp_generate_articles_auto_action', 'autobp_generate_articles_auto_nonce' ); ?>

            <table class="form-table">
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
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Otimização SEO', 'autoblogpro' ); ?></th>
                    <td>
                        <label for="autobp_generate_meta_description_auto">
                            <input type="checkbox" name="autobp_generate_meta_description_auto" id="autobp_generate_meta_description_auto" value="1" checked>
                            <?php esc_html_e( 'Gerar Meta Descrição automaticamente', 'autoblogpro' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Gerar Artigos', 'autoblogpro' ) ); ?>
        </form>
        <?php
    }

    /**
     * Renderiza o conteúdo para a aba "Modo URL Base".
     *
     * Este método exibe a interface para o modo de geração de artigos baseado em URL.
     * Consiste em duas partes principais:
     * 1. Formulário de Submissão de URL:
     *    - Campo para o usuário inserir a URL do artigo de origem.
     *    - Botão "Extrair Conteúdo da URL" que submete para a action `admin_post_autobp_process_url_base`.
     *    - Nonce (`autobp_process_url_base_nonce`) e campo oculto `tab=url_mode` são incluídos.
     *
     * 2. Área de Exibição de Dados Extraídos e Formulário de Reescrita:
     *    - Se dados extraídos estiverem presentes no transient (`autobp_extracted_data_{user_id}`),
     *      eles são exibidos. Isso inclui:
     *        - O texto principal extraído da URL.
     *        - Palavras-chave e tópicos identificados pela análise da IA.
     *        - Insights adicionais encontrados pela IA.
     *        - Mensagens de status sobre a análise e busca de insights.
     *    - Formulário de Reescrita:
     *        - Checkbox "Gerar Meta Descrição": Permite ao usuário optar pela geração automática
     *          de uma meta descrição para o artigo reescrito.
     *        - Botão "Reescrever e Gerar Artigo" que submete para `admin_post_autobp_rewrite_url_content`.
     *        - Nonce (`autobp_rewrite_url_content_nonce`) e campo oculto `tab=url_mode`.
     *    - Botão "Limpar Dados Extraídos" que chama `admin_post_autobp_clear_extracted_text`.
     *
     * Mensagens de feedback de todas as actions (`autobp_process_url_base`,
     * `autobp_rewrite_url_content`, `autobp_clear_extracted_text`) são exibidas no topo
     * desta aba, geralmente passadas via query arg `autobp_url_message` na URL de redirecionamento.
     * Detalhes de erros específicos (ex: da API OpenAI durante a reescrita) podem ser
     * armazenados no transient e exibidos também.
     *
     * @since 0.2.0 (Refatorado de `render_generate_articles_page` e `render_url_mode_form_and_data`)
     * @access private
     * @static
     */
    private static function render_url_mode_form_content() {
        // Exibir mensagens de feedback para o formulário de URL
        // Estas mensagens são geralmente passadas por admin-post.php via add_query_arg
        if ( isset( $_GET['autobp_url_message'] ) ) {
            $url_message_code = sanitize_key( $_GET['autobp_url_message'] );
            $url_messages = array(
                'url_missing' => array( 'type' => 'error', 'text' => __( 'A URL de origem é obrigatória.', 'autoblogpro' ) ),
                'invalid_url_format' => array( 'type' => 'error', 'text' => __( 'O formato da URL fornecida é inválido.', 'autoblogpro' ) ),
                'url_fetch_failed' => array( 'type' => 'error', 'text' => __( 'Falha ao buscar o conteúdo da URL. Verifique a URL e a conectividade.', 'autoblogpro' ) ),
                'url_invalid_response' => array( 'type' => 'error', 'text' => __( 'A URL não retornou um código de status HTTP 200 (OK).', 'autoblogpro' ) ),
                'url_empty_content' => array( 'type' => 'error', 'text' => __( 'O conteúdo da URL parece estar vazio.', 'autoblogpro' ) ),
                'no_html_to_parse' => array( 'type' => 'error', 'text' => __( 'Nenhum conteúdo HTML foi buscado para análise.', 'autoblogpro' ) ),
                'text_extraction_failed' => array( 'type' => 'error', 'text' => __( 'Não foi possível extrair o conteúdo principal do artigo da URL. O site pode ter uma estrutura complexa.', 'autoblogpro' ) ),
                'extraction_success' => array( 'type' => 'success', 'text' => __( 'Conteúdo extraído e analisado com sucesso!', 'autoblogpro' ) ),
                'transient_cleared' => array( 'type' => 'info', 'text' => __( 'Conteúdo extraído e área de texto limpos.', 'autoblogpro' ) ),
                'no_extracted_data_to_rewrite' => array( 'type' => 'warning', 'text' => __( 'Não há dados extraídos para reescrever. Por favor, extraia o conteúdo de uma URL primeiro.', 'autoblogpro' ) ),
                'rewrite_skipped_no_api_key' => array( 'type' => 'warning', 'text' => __( 'Reescrita pulada: A chave da API OpenAI não está configurada.', 'autoblogpro' ) ),
                'rewrite_failed' => array( 'type' => 'error', 'text' => __( 'Falha ao processar a reescrita do conteúdo.', 'autoblogpro' ) ),
                'rewrite_save_failed' => array( 'type' => 'error', 'text' => __( 'O conteúdo foi reescrito, mas falhou ao salvar o novo artigo como rascunho.', 'autoblogpro' ) ),
            );

            $user_id_temp = get_current_user_id();
            $transient_name_temp = 'autobp_extracted_data_' . $user_id_temp;
            $extracted_data_for_error = get_transient($transient_name_temp);

            if (isset($extracted_data_for_error['rewrite_error_message']) && !empty($extracted_data_for_error['rewrite_error_message'])) {
                echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__('Detalhe da Falha na Reescrita:', 'autoblogpro') . '</strong> ' . esc_html($extracted_data_for_error['rewrite_error_message']) . '</p></div>';
            }

            if ( isset( $url_messages[$url_message_code] ) ) {
                $message = $url_messages[$url_message_code];
                echo '<div class="notice notice-' . esc_attr( $message['type'] ) . ' is-dismissible"><p>' . esc_html( $message['text'] ) . '</p></div>';
            }
        }
        ?>
        <h3><?php esc_html_e( 'Modo URL Base', 'autoblogpro' ); ?></h3>
        <p><?php esc_html_e( 'Extraia o conteúdo principal de um artigo existente na web para usá-lo como base para novas ideias ou reescrita.', 'autoblogpro' ); ?></p>
        
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="url-mode-form">
            <input type="hidden" name="action" value="autobp_process_url_base">
            <input type="hidden" name="tab" value="url_mode"> <?php // Para manter a aba ativa no redirecionamento ?>
            <?php wp_nonce_field( 'autobp_process_url_base_action', 'autobp_process_url_base_nonce' ); ?>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="autobp_source_url"><?php esc_html_e( 'URL do Artigo de Origem:', 'autoblogpro' ); ?></label>
                    </th>
                    <td>
                        <input type="url" id="autobp_source_url" name="autobp_source_url" class="large-text" required placeholder="https://exemplo.com/artigo-original" value="<?php echo isset($_GET['autobp_source_url_submitted']) ? esc_url($_GET['autobp_source_url_submitted']) : ''; ?>">
                        <p class="description"><?php esc_html_e( 'Insira a URL completa do artigo que você deseja usar como base.', 'autoblogpro' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Extrair Conteúdo da URL', 'autoblogpro' ) ); ?>
        </form>

        <?php
        $user_id = get_current_user_id();
        $transient_name = 'autobp_extracted_data_' . $user_id;
        $extracted_data = get_transient( $transient_name );

        if ( $extracted_data && is_array( $extracted_data ) ) :
            $extracted_text = isset($extracted_data['text']) ? $extracted_data['text'] : '';
            $keywords = isset($extracted_data['keywords']) ? $extracted_data['keywords'] : array();
            $topics = isset($extracted_data['topics']) ? $extracted_data['topics'] : array();
            $analysis_message = isset($extracted_data['analysis_message']) ? $extracted_data['analysis_message'] : '';
            $additional_insights = isset($extracted_data['additional_insights']) ? $extracted_data['additional_insights'] : array();
            $insights_message = isset($extracted_data['insights_message']) ? $extracted_data['insights_message'] : '';

            if ( !empty($extracted_text) ):
                $clear_transient_nonce = wp_create_nonce('autobp_clear_extracted_text_nonce');
                // Adiciona o parâmetro tab ao link de limpar
                $clear_transient_link = admin_url('admin-post.php?action=autobp_clear_extracted_text&tab=url_mode&_wpnonce=' . $clear_transient_nonce);
            ?>
                <div id="extracted-content-section" style="margin-top:20px; padding:15px; border:1px solid #e0e0e0; background-color:#fff;">
                    <h4><?php esc_html_e( 'Conteúdo Extraído da URL:', 'autoblogpro' ); ?></h4>
                    <textarea readonly style="width:100%; height: 200px; background-color:#f9f9f9; padding:10px; border:1px solid #ddd; margin-bottom:10px;"><?php echo esc_textarea( $extracted_text ); ?></textarea>

                    <?php if ( ! empty( $analysis_message ) ) : ?>
                        <p><strong><?php esc_html_e( 'Status da Análise Semântica:', 'autoblogpro' ); ?></strong> <?php echo esc_html( $analysis_message ); ?></p>
                    <?php endif; ?>

                    <?php if ( ! empty( $keywords ) ) : ?>
                        <p><strong><?php esc_html_e( 'Palavras-chave Identificadas:', 'autoblogpro' ); ?></strong> <?php echo esc_html( implode( ', ', $keywords ) ); ?></p>
                    <?php endif; ?>

                    <?php if ( ! empty( $topics ) ) : ?>
                        <p><strong><?php esc_html_e( 'Tópicos Principais Identificados:', 'autoblogpro' ); ?></strong></p>
                        <ul style="list-style-type:disc; margin-left:20px;">
                            <?php foreach ( $topics as $topic ) : ?>
                                <li><?php echo esc_html( $topic ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $insights_message ) ) : ?>
                         <p style="margin-top:10px;"><strong><?php esc_html_e( 'Status da Pesquisa de Insights Adicionais:', 'autoblogpro' ); ?></strong> <?php echo esc_html( $insights_message ); ?></p>
                    <?php endif; ?>

                    <?php if ( ! empty( $additional_insights ) ) : ?>
                        <p><strong><?php esc_html_e( 'Insights Adicionais Encontrados:', 'autoblogpro' ); ?></strong></p>
                        <ul style="list-style-type:disc; margin-left:20px;">
                            <?php foreach ( $additional_insights as $insight ) : ?>
                                <li><?php echo esc_html( $insight ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <div style="margin-top:20px;">
                         <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block; margin-right:10px;">
                            <input type="hidden" name="action" value="autobp_rewrite_url_content">
                            <input type="hidden" name="tab" value="url_mode"> 
                            <?php wp_nonce_field( 'autobp_rewrite_url_content_action', 'autobp_rewrite_url_content_nonce' ); ?>
                            
                            <p style="margin-bottom: 15px;">
                                <label for="autobp_generate_meta_description_url">
                                    <input type="checkbox" name="autobp_generate_meta_description_url" id="autobp_generate_meta_description_url" value="1" checked>
                                    <?php esc_html_e( 'Gerar Meta Descrição automaticamente para o artigo reescrito', 'autoblogpro' ); ?>
                                </label>
                            </p>
                            <?php submit_button( __( 'Reescrever e Gerar Artigo', 'autoblogpro' ), 'primary', 'submit_rewrite', false ); ?>
                        </form>
                        <a href="<?php echo esc_url($clear_transient_link); ?>" class="button"><?php esc_html_e( 'Limpar Dados Extraídos', 'autoblogpro' ); ?></a>
                    </div>
                </div>
            <?php
            endif; 
        endif; 
        ?>
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
     * (ex: após uma ação de publicação, lixeira, restauração, etc.).
     * Adiciona links de filtro de status (subsubsub) para "Todos", "Publicados", "Rascunhos",
     * "Agendados" e "Lixeira", com contagens para cada status.
     * Ações de post (como "Restaurar", "Excluir Permanentemente") são exibidas condicionalmente
     * com base no status do post atual.
     * Inclui um botão "Buscar Imagem" para cada post, que dispara uma chamada AJAX para a API Pexels
     * e exibe os resultados. Um handler JavaScript adicional lida com a ação de definir uma imagem
     * selecionada da Pexels como imagem destacada do post.
     *
     * @since 0.1.0 (Funcionalidades de Lixeira e Pexels adicionadas em 0.2.0)
     * @access public
     * @static
     */
    public static function render_article_library_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Biblioteca de Artigos Gerados', 'autoblogpro' ) . '</h1>';

        // Exibe mensagens de feedback (ex: após publicar um post via admin-post.php).
        // Inclui mensagens para publicar, lixeira, agendar, regenerar, restaurar, excluir permanentemente.
        if ( isset( $_GET['autobp_message'] ) && isset( $_GET['post_id'] ) ) {
            $message_type = sanitize_key( $_GET['autobp_message'] ); 
            $post_id_feedback = intval( $_GET['post_id'] ); 
            $post_title_feedback = '';
            if ($post_id_feedback) {
                // Para 'post_deleted_permanently', o post pode não existir mais.
                // Se $_GET['post_title'] foi passado, use-o. Senão, tente get_the_title.
                if ($message_type === 'post_deleted_permanently' && isset($_GET['post_title'])) {
                    $post_title_feedback = esc_html(urldecode($_GET['post_title']));
                } else {
                    $post_title_feedback = $post_id_feedback ? get_the_title( $post_id_feedback ) : '';
                }
            }


            if ( $message_type === 'post_published' && $post_title_feedback ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( 'Post "%s" publicado com sucesso!', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'publish_failed' && $post_title_feedback ) {
                 echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'Falha ao publicar o post "%s".', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'publish_permission_denied' && $post_title_feedback ) {
                 echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'Você não tem permissão para publicar o post "%s".', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'invalid_post_id' ) { 
                 echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'ID do post inválido para a ação solicitada.', 'autoblogpro' ) . '</p></div>';
            } elseif ( $message_type === 'post_trashed' && $post_title_feedback ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( 'Post "%s" movido para a lixeira com sucesso.', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'trash_failed' && $post_title_feedback ) {
                 echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'Falha ao mover o post "%s" para a lixeira.', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'trash_permission_denied' && $post_title_feedback ) {
                 echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'Você não tem permissão para mover o post "%s" para a lixeira.', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'post_scheduled' && $post_title_feedback ) {
                $scheduled_date = isset($_GET['scheduled_date']) ? sanitize_text_field(wp_unslash($_GET['scheduled_date'])) : '';
                echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( 'Post "%1$s" agendado para %2$s com sucesso.', 'autoblogpro' ), esc_html( $post_title_feedback ), esc_html($scheduled_date) ) . '</p></div>';
            } elseif ( $message_type === 'schedule_failed' && $post_title_feedback ) {
                 echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'Falha ao agendar o post "%s".', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'schedule_permission_denied' && $post_title_feedback ) {
                 echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'Você não tem permissão para agendar o post "%s".', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'schedule_invalid_date' && $post_title_feedback ) {
                 echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'Data ou hora inválida para o agendamento do post "%s". A data deve ser no futuro.', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'regenerate_success' && $post_title_feedback ) {
                $new_post_id_feedback = isset($_GET['new_post_id']) ? intval($_GET['new_post_id']) : 0;
                $new_post_title_feedback = $new_post_id_feedback ? get_the_title($new_post_id_feedback) : '';
                $edit_link_feedback = $new_post_id_feedback ? get_edit_post_link($new_post_id_feedback) : '';
                if ($new_post_title_feedback && $edit_link_feedback) {
                     // translators: %1$s Título do post original, %2$s Título do novo rascunho (com link)
                    echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( wp_kses_post(__( 'Artigo "%1$s" regenerado com sucesso! Novo rascunho: <a href="%2$s" target="_blank">"%3$s"</a>.', 'autoblogpro' )), esc_html($post_title_feedback), esc_url($edit_link_feedback), esc_html($new_post_title_feedback) ) . '</p></div>';
                } else {
                     echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Artigo regenerado com sucesso e salvo como um novo rascunho.', 'autoblogpro' ) . '</p></div>';
                }
            } elseif ( $message_type === 'regenerate_failed' && $post_title_feedback ) {
                 echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'Falha ao regenerar o artigo "%s".', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'regenerate_missing_params' && $post_title_feedback ) {
                 echo '<div class="notice notice-warning is-dismissible"><p>' . sprintf( esc_html__( 'Não foi possível regenerar o artigo "%s" pois os parâmetros de geração originais não foram encontrados.', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'post_restored' && $post_title_feedback ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( 'Post "%s" restaurado da lixeira com sucesso.', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'restore_failed' && $post_title_feedback ) {
                echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'Falha ao restaurar o post "%s" da lixeira.', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            } elseif ( $message_type === 'post_deleted_permanently' ) { 
                // Para post_deleted_permanently, $post_title_feedback pode já ter sido preenchido com o título recuperado via $_GET['post_title']
                // ou será apenas o ID se o título não foi passado.
                $display_name = !empty($post_title_feedback) ? $post_title_feedback : __('ID: ','autoblogpro') . $post_id_feedback;
                echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( 'Post "%s" excluído permanentemente com sucesso.', 'autoblogpro' ), esc_html( $display_name ) ) . '</p></div>';
            } elseif ( $message_type === 'delete_permanent_failed' && $post_title_feedback ) {
                echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'Falha ao excluir permanentemente o post "%s".', 'autoblogpro' ), esc_html( $post_title_feedback ) ) . '</p></div>';
            }
        }

        // Coletar valores dos filtros
        $selected_niche_id = isset( $_GET['filter_niche_id'] ) ? intval( $_GET['filter_niche_id'] ) : '';
        // $selected_post_status = isset( $_GET['filter_post_status'] ) ? sanitize_key( $_GET['filter_post_status'] ) : '';
        // O filtro de status principal agora virá de 'post_status_filter' dos links subsubsub
        $current_status_filter = isset( $_GET['post_status_filter'] ) ? sanitize_key( $_GET['post_status_filter'] ) : '';


        // Calcular contagens para os links subsubsub
        $statuses_to_count = array('publish', 'draft', 'future', 'pending', 'private', 'trash');
        $total_counts = array('all' => 0);
        foreach ($statuses_to_count as $status) {
            $query_args_count = array(
                'post_type' => 'post',
                'post_status' => $status,
                'meta_query' => array(
                    array(
                        'key'     => '_autobp_generated_from_niche_id', // Apenas gerados pelo plugin
                        'compare' => 'EXISTS',
                    ),
                ),
                'posts_per_page' => -1, // Contar todos os posts
                'fields' => 'ids'       // Apenas IDs para performance
            );
            $posts_in_status = new WP_Query($query_args_count);
            $total_counts[$status] = $posts_in_status->post_count; // Use post_count for accurate count of items returned
            if ($status !== 'trash' && $status !== 'auto-draft') { 
                 $total_counts['all'] += $posts_in_status->post_count;
            }
        }
        // Se nenhum filtro de status específico estiver ativo, 'all' deve ser a soma de todos os não-lixeira
        // Se um filtro estiver ativo, 'all' já foi calculado.
        // Se current_status_filter for vazio, estamos em "Todos", então 'all' já está correto.
        
        ?>
        <ul class="subsubsub">
            <li class="all"><a href="?page=autobp-article-library" class="<?php echo empty($current_status_filter) ? 'current' : ''; ?>"><?php esc_html_e('Todos'); ?> <span class="count">(<?php echo esc_html( $total_counts['all'] ); ?>)</span></a> |</li>
            <li class="publish"><a href="?page=autobp-article-library&post_status_filter=publish" class="<?php echo ($current_status_filter === 'publish') ? 'current' : ''; ?>"><?php esc_html_e('Publicados'); ?> <span class="count">(<?php echo esc_html( $total_counts['publish'] ); ?>)</span></a> |</li>
            <li class="draft"><a href="?page=autobp-article-library&post_status_filter=draft" class="<?php echo ($current_status_filter === 'draft') ? 'current' : ''; ?>"><?php esc_html_e('Rascunhos'); ?> <span class="count">(<?php echo esc_html( $total_counts['draft'] ); ?>)</span></a> |</li>
            <li class="future"><a href="?page=autobp-article-library&post_status_filter=future" class="<?php echo ($current_status_filter === 'future') ? 'current' : ''; ?>"><?php esc_html_e('Agendados'); ?> <span class="count">(<?php echo esc_html( $total_counts['future'] ); ?>)</span></a> |</li>
            <li class="trash"><a href="?page=autobp-article-library&post_status_filter=trash" class="<?php echo ($current_status_filter === 'trash') ? 'current' : ''; ?>"><?php esc_html_e('Lixeira'); ?> <span class="count">(<?php echo esc_html( $total_counts['trash'] ); ?>)</span></a></li>
        </ul>
        <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
            <input type="hidden" name="page" value="autobp-article-library" />
            <?php if (!empty($current_status_filter)): // Manter o filtro de status se estiver ativo ?>
                <input type="hidden" name="post_status_filter" value="<?php echo esc_attr($current_status_filter); ?>" />
            <?php endif; ?>
            <div class="alignleft actions">
                <?php
                // Filtro de Nicho
                $niche_cpt_posts = get_posts( array( 'post_type' => 'niche', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
                if ( ! empty( $niche_cpt_posts ) ) {
                    echo '<select name="filter_niche_id">';
                    echo '<option value="">' . esc_html__( 'Todos os Nichos', 'autoblogpro' ) . '</option>';
                    foreach ( $niche_cpt_posts as $niche_post_item ) {
                        echo '<option value="' . esc_attr( $niche_post_item->ID ) . '"' . selected( $selected_niche_id, $niche_post_item->ID, false ) . '>' . esc_html( $niche_post_item->post_title ) . '</option>';
                    }
                    echo '</select>';
                }

                <?php /* O filtro de status agora é pelos links subsubsub, este select pode ser removido ou adaptado
                // Filtro de Status
                // Usar get_post_stati para obter status de forma mais dinâmica e com rótulos corretos
                // $stati = get_post_stati( array('show_in_admin_status_list' => true ), 'objects' );
                // if ( !empty($stati) ) {
                //     echo '<select name="filter_post_status">'; // Este name precisa ser diferente de post_status_filter ou coordenado
                //     echo '<option value="">' . esc_html__( 'Todos os Status (do filtro select)', 'autoblogpro' ) . '</option>';
                //     $display_stati = array('publish', 'draft', 'pending', 'future', 'private', 'trash'); 
                //     foreach ( $display_stati as $status_slug ) {
                //         $status_object = get_post_status_object( $status_slug );
                //         if ($status_object) {
                //              echo '<option value="' . esc_attr( $status_slug ) . '"' . selected( $current_status_filter, $status_slug, false ) . '>' . esc_html( $status_object->label ) . '</option>';
                //         }
                //     }
                //     echo '</select>';
                // }
                */ ?>
                <input type="submit" class="button" value="<?php esc_attr_e( 'Filtrar Nicho', 'autoblogpro' ); ?>">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=autobp-article-library' ) ); ?>" class="button"><?php esc_html_e( 'Limpar Filtros', 'autoblogpro' ); ?></a>
            </div>
        </form>
        <br class="clear">
        <?php

        // Configurações de paginação
        $posts_per_page = 20; 
        $current_page = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;
        $current_page = max( 1, $current_page ); 

        // Modificar Argumentos da WP_Query com base nos filtros
        $args = array(
            'post_type'      => 'post',
            'posts_per_page' => $posts_per_page,
            'paged'          => $current_page,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_autobp_generated_from_niche_id',
                    'compare' => 'EXISTS', 
                ),
            ),
        );

        if ( $selected_niche_id > 0 ) {
            $args['meta_query'][] = array(
                'key'     => '_autobp_generated_from_niche_id',
                'value'   => $selected_niche_id,
                'compare' => '=',
                'type'    => 'NUMERIC',
            );
        }

        // if ( ! empty( $selected_post_status ) ) { // Este era o select filter
        //     $args['post_status'] = $selected_post_status;
        // } else
        if ( ! empty( $current_status_filter ) ) { // Este é o filtro dos links subsubsub
             $args['post_status'] = $current_status_filter;
        } else {
            // Se nenhum filtro de status específico (incluindo 'trash') estiver ativo, listar todos exceto lixeira por padrão.
            // A contagem 'all' já reflete isso.
            $args['post_status'] = array('publish', 'draft', 'pending', 'future', 'private');
        }


        $article_query = new WP_Query( $args );

        if ( ! $article_query->have_posts() ) {
            echo '<p>' . esc_html__( 'Nenhum artigo gerado encontrado com os filtros atuais.', 'autoblogpro' ) . '</p>';
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

            // Loop através dos posts encontrados
            while ( $article_query->have_posts() ) {
                $article_query->the_post(); 
                
                $post_id = get_the_ID();
                $title = get_the_title(); // Não precisa de $post_id aqui pois the_post() já configurou
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
                 // Ações condicionais baseadas no status do post
                if ( get_post_status( $post_id ) === 'trash' ) {
                    // Ações para posts na lixeira
                    $restore_nonce = wp_create_nonce( 'autobp_restore_post_' . $post_id );
                    $restore_link = admin_url( 'admin-post.php?action=autobp_restore_post&post_id=' . $post_id . '&_wpnonce=' . $restore_nonce . '&post_status_filter=trash' );
                    echo ' | <span class="untrash"><a href="' . esc_url( $restore_link ) . '">' . esc_html__( 'Restaurar', 'autoblogpro' ) . '</a></span>';

                    $delete_perm_nonce = wp_create_nonce( 'autobp_delete_permanent_post_' . $post_id );
                    $delete_perm_link = admin_url( 'admin-post.php?action=autobp_delete_permanent_post&post_id=' . $post_id . '&_wpnonce=' . $delete_perm_nonce . '&post_status_filter=trash' );
                    echo ' | <span class="delete"><a href="' . esc_url( $delete_perm_link ) . '" style="color:red;" onclick="return confirm(\'' . esc_js( __( 'Tem certeza que deseja excluir este item permanentemente? Esta ação não pode ser desfeita.', 'autoblogpro' ) ) . '\');">' . esc_html__( 'Excluir Permanentemente', 'autoblogpro' ) . '</a></span>';
                } else {
                    // Ações para posts não na lixeira
                    if ( current_user_can( 'delete_post', $post_id ) ) {
                        $trash_nonce = wp_create_nonce( 'autobp_trash_post_' . $post_id );
                        $trash_link = admin_url( 'admin-post.php?action=autobp_trash_post&post_id=' . $post_id . '&_wpnonce=' . $trash_nonce . ( !empty($current_status_filter) ? '&post_status_filter='.$current_status_filter : '' ) );
                        echo ' | <span class="trash"><a href="' . esc_url( $trash_link ) . '" style="color: red;" onclick="return confirm(\'' . esc_js( __( 'Tem certeza que deseja mover este item para a lixeira?', 'autoblogpro' ) ) . '\');" aria-label="' . esc_attr__('Mover este item para a lixeira', 'autoblogpro') . '">' . esc_html__( 'Lixeira', 'autoblogpro' ) . '</a></span>';
                    }
                    if ( get_post_status( $post_id ) === 'draft' && current_user_can( 'publish_post', $post_id ) ) {
                        $publish_nonce = wp_create_nonce( 'autobp_publish_post_' . $post_id );
                        $publish_link = admin_url( 'admin-post.php?action=autobp_publish_post&post_id=' . $post_id . '&_wpnonce=' . $publish_nonce . ( !empty($current_status_filter) ? '&post_status_filter='.$current_status_filter : '' ) );
                        echo ' | <span class="publish"><a href="' . esc_url( $publish_link ) . '" style="color: green;" aria-label="' . esc_attr__('Publicar este rascunho', 'autoblogpro') . '">' . esc_html__( 'Publicar', 'autoblogpro' ) . '</a></span>';
                    }
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
                    $publish_nonce_col = wp_create_nonce( 'autobp_publish_post_' . $post_id ); 
                    $publish_link_col = admin_url( 'admin-post.php?action=autobp_publish_post&post_id=' . $post_id . '&_wpnonce=' . $publish_nonce_col );
                    echo '<a href="' . esc_url( $publish_link_col ) . '" class="button button-small button-primary autobp-action-button">' . esc_html__( 'Publicar', 'autoblogpro' ) . '</a> ';
                }
                if ( get_post_status( $post_id ) === 'draft' && current_user_can( 'edit_post', $post_id ) && current_user_can( 'publish_posts') ) {
                     echo '<a href="#" class="button button-small autobp-schedule-action autobp-action-button" data-postid="' . esc_attr( $post_id ) . '">' . esc_html__( 'Agendar', 'autoblogpro' ) . '</a> ';
                }
                
                // Botão Regenerar Artigo
                $regenerate_nonce = wp_create_nonce( 'autobp_regenerate_post_' . $post_id );
                $regenerate_link = admin_url( 'admin-post.php?action=autobp_regenerate_post&post_id=' . $post_id . '&_wpnonce=' . $regenerate_nonce );
                echo '<a href="' . esc_url( $regenerate_link ) . '" class="button button-small autobp-action-button" onclick="return confirm(\'' . esc_js( __( 'Tem certeza que deseja regenerar este artigo? Um novo rascunho será criado com base nos parâmetros originais (ou da URL de origem).', 'autoblogpro' ) ) . '\');">' . esc_html__( 'Regenerar', 'autoblogpro' ) . '</a> ';

                // Botão Verificar Plágio
                $copyscape_username = get_option('autobp_copyscape_username', '');
                $copyscape_api_key = get_option('autobp_copyscape_api_key', '');
                $copyscape_configured = !empty($copyscape_username) && !empty($copyscape_api_key);
                
                echo '<button type="button" class="button autobp-check-plagiarism autobp-action-button" data-postid="' . esc_attr( $post_id ) . '" ';
                if (!$copyscape_configured) {
                    echo 'disabled="disabled" title="' . esc_attr__('Configure as credenciais da API Copyscape nas Configurações do AutoBlogPro.', 'autoblogpro') . '"';
                }
                echo '>' . esc_html__( 'Verificar Plágio', 'autoblogpro' ) . '</button> ';
                
                // Botão Buscar Imagem Pexels
                $pexels_api_key_configured = !empty(get_option('autobp_pexels_api_key'));
                echo '<button type="button" class="button autobp-fetch-pexels-images autobp-action-button" data-postid="' . esc_attr( $post_id ) . '" data-posttitle="' . esc_attr( $title ) . '" ';
                if (!$pexels_api_key_configured) {
                    echo 'disabled="disabled" title="' . esc_attr__('Configure a chave da API Pexels nas Configurações do AutoBlogPro.', 'autoblogpro') . '"';
                }
                echo '>' . esc_html__( 'Buscar Imagem', 'autoblogpro' ) . '</button> ';

                // Ações explícitas na coluna de Ações
                if ( get_post_status( $post_id ) === 'trash' ) {
                    $restore_nonce_col = wp_create_nonce( 'autobp_restore_post_' . $post_id );
                    $restore_link_col = admin_url( 'admin-post.php?action=autobp_restore_post&post_id=' . $post_id . '&_wpnonce=' . $restore_nonce_col . '&post_status_filter=trash');
                    echo '<a href="' . esc_url( $restore_link_col ) . '" class="button button-small autobp-action-button">' . esc_html__( 'Restaurar', 'autoblogpro' ) . '</a> ';

                    $delete_perm_nonce_col = wp_create_nonce( 'autobp_delete_permanent_post_' . $post_id );
                    $delete_perm_link_col = admin_url( 'admin-post.php?action=autobp_delete_permanent_post&post_id=' . $post_id . '&_wpnonce=' . $delete_perm_nonce_col . '&post_status_filter=trash');
                    echo '<a href="' . esc_url( $delete_perm_link_col ) . '" class="button button-small autobp-action-button autobp-delete-permanent-button" style="color:red;" onclick="return confirm(\'' . esc_js( __( 'Tem certeza que deseja excluir este item permanentemente? Esta ação não pode ser desfeita.', 'autoblogpro' ) ) . '\');">' . esc_html__( 'Excluir Permanentemente', 'autoblogpro' ) . '</a> ';
                } else {
                    // Botões existentes para posts não na lixeira
                    if ( get_post_status( $post_id ) === 'draft' && current_user_can( 'publish_post', $post_id ) ) {
                         $publish_nonce_col = wp_create_nonce( 'autobp_publish_post_' . $post_id ); 
                         $publish_link_col = admin_url( 'admin-post.php?action=autobp_publish_post&post_id=' . $post_id . '&_wpnonce=' . $publish_nonce_col . ( !empty($current_status_filter) ? '&post_status_filter='.$current_status_filter : '' ) );
                         echo '<a href="' . esc_url( $publish_link_col ) . '" class="button button-small button-primary autobp-action-button">' . esc_html__( 'Publicar', 'autoblogpro' ) . '</a> ';
                    }
                    if ( get_post_status( $post_id ) === 'draft' && current_user_can( 'edit_post', $post_id ) && current_user_can( 'publish_posts') ) {
                         echo '<a href="#" class="button button-small autobp-schedule-action autobp-action-button" data-postid="' . esc_attr( $post_id ) . '">' . esc_html__( 'Agendar', 'autoblogpro' ) . '</a> ';
                    }
                    $regenerate_nonce = wp_create_nonce( 'autobp_regenerate_post_' . $post_id );
                    $regenerate_link = admin_url( 'admin-post.php?action=autobp_regenerate_post&post_id=' . $post_id . '&_wpnonce=' . $regenerate_nonce . ( !empty($current_status_filter) ? '&post_status_filter='.$current_status_filter : '' ) );
                    echo '<a href="' . esc_url( $regenerate_link ) . '" class="button button-small autobp-action-button" onclick="return confirm(\'' . esc_js( __( 'Tem certeza que deseja regenerar este artigo? Um novo rascunho será criado com base nos parâmetros originais (ou da URL de origem).', 'autoblogpro' ) ) . '\');">' . esc_html__( 'Regenerar', 'autoblogpro' ) . '</a> ';

                    $pexels_api_key_configured = !empty(get_option('autobp_pexels_api_key'));
                    echo '<button type="button" class="button autobp-fetch-pexels-images autobp-action-button" data-postid="' . esc_attr( $post_id ) . '" data-posttitle="' . esc_attr( $title ) . '" ';
                    if (!$pexels_api_key_configured) {
                        echo 'disabled="disabled" title="' . esc_attr__('Configure a chave da API Pexels nas Configurações do AutoBlogPro.', 'autoblogpro') . '"';
                    }
                    echo '>' . esc_html__( 'Buscar Imagem', 'autoblogpro' ) . '</button> ';
                    
                    if ( current_user_can( 'delete_post', $post_id ) ) {
                        $trash_nonce_col = wp_create_nonce( 'autobp_trash_post_' . $post_id );
                        $trash_link_col = admin_url( 'admin-post.php?action=autobp_trash_post&post_id=' . $post_id . '&_wpnonce=' . $trash_nonce_col . ( !empty($current_status_filter) ? '&post_status_filter='.$current_status_filter : '' ) );
                        echo '<a href="' . esc_url( $trash_link_col ) . '" class="button button-small autobp-action-button autobp-trash-button" onclick="return confirm(\'' . esc_js( __( 'Tem certeza que deseja mover este item para a lixeira?', 'autoblogpro' ) ) . '\');">' . esc_html__( 'Lixeira', 'autoblogpro' ) . '</a>';
                    }
                }
                echo '<br><span class="spinner" id="autobp-plagiarism-spinner-' . esc_attr( $post_id ) . '" style="display:none; float:none; vertical-align: middle; margin-top: 5px;"></span>';
                echo '<span class="autobp-plagiarism-result" id="autobp-plagiarism-result-' . esc_attr( $post_id ) . '" style="display:none; margin-left:0; margin-top: 5px; display:inline-block;"></span>';
                
                // Container para resultados Pexels e spinner Pexels
                echo '<div class="autobp-pexels-results" id="autobp-pexels-results-' . esc_attr( $post_id ) . '" style="display:none; margin-top:10px; clear:both;"></div>';
                echo '<span class="spinner" id="autobp-pexels-spinner-' . esc_attr( $post_id ) . '" style="display:none; float:none; vertical-align: middle; margin-top:5px; clear:both;"></span>';
                
                echo '</td>';
                echo '</tr>';
                // Linha oculta para o formulário de agendamento
                ?>
                <tr id="autobp-schedule-form-<?php echo esc_attr( $post_id ); ?>" class="autobp-schedule-form-row" style="display:none;">
                    <td colspan="5">
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="autobp-schedule-form-inner">
                            <input type="hidden" name="action" value="autobp_schedule_post">
                            <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
                            <?php wp_nonce_field( 'autobp_schedule_post_' . $post_id, '_wpnonce_autobp_schedule' ); ?>
                            
                            <label for="autobp_schedule_date_<?php echo esc_attr( $post_id ); ?>"><?php esc_html_e( 'Data:', 'autoblogpro' ); ?></label>
                            <input type="date" id="autobp_schedule_date_<?php echo esc_attr( $post_id ); ?>" name="autobp_schedule_date" required>
                            
                            <label for="autobp_schedule_time_<?php echo esc_attr( $post_id ); ?>"><?php esc_html_e( 'Hora:', 'autoblogpro' ); ?></label>
                            <input type="time" id="autobp_schedule_time_<?php echo esc_attr( $post_id ); ?>" name="autobp_schedule_time" required>
                            
                            <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Salvar Agendamento', 'autoblogpro' ); ?>">
                            <button type="button" class="button autobp-cancel-schedule" data-postid="<?php echo esc_attr( $post_id ); ?>"><?php esc_html_e( 'Cancelar', 'autoblogpro' ); ?></button>
                        </form>
                    </td>
                </tr>
                <?php
            }
            wp_reset_postdata(); 
            echo '</tbody></table>';

            // Adicionar links de paginação
            if ( $article_query->max_num_pages > 1 ) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo paginate_links( array(
                    // Construir URL base para paginação mantendo os filtros ativos.
                    $base_url_for_pagination_args = array('page' => 'autobp-article-library');
                    if ( ! empty( $selected_niche_id ) ) {
                        $base_url_for_pagination_args['filter_niche_id'] = $selected_niche_id;
                    }
                    if ( ! empty( $current_status_filter ) ) { // Usar o filtro de status dos links subsubsub
                        $base_url_for_pagination_args['post_status_filter'] = $current_status_filter;
                    }
                    
                    'base'         => add_query_arg( 'paged', '%#%', admin_url( 'admin.php?' . http_build_query($base_url_for_pagination_args) ) ),
                    'format'       => '', // Removido para não ter 'format' na URL, já que 'paged' está na query string
                    'total'        => $article_query->max_num_pages,
                    'current'      => $current_page,
                    'show_all'     => false,
                    'end_size'     => 1,
                    'mid_size'     => 2,
                    'prev_next'    => true,
                    'prev_text'    => __('&laquo; Anterior', 'autoblogpro'),
                    'next_text'    => __('Próximo &raquo;', 'autoblogpro'),
                    'type'         => 'plain', 
                ) );
                echo '</div></div>';
            }
        }
        echo '</div>'; // Fim do .wrap
        ?>
        <style type="text/css">
            .autobp-schedule-form-row td { padding: 10px; background-color: #f9f9f9; }
            .autobp-schedule-form-inner label { margin-right: 5px; }
            .autobp-schedule-form-inner input[type="date"],
            .autobp-schedule-form-inner input[type="time"] { margin-right: 15px; }
            .autobp-action-button { margin-bottom: 5px !important; } /* Adiciona margem inferior aos botões de ação */
            .autobp-trash-button { color: red !important; border-color: red !important; }
            .autobp-trash-button:hover { color: #fff !important; background-color: red !important; }

        </style>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Handler para Agendamento (existente)
            $('.autobp-schedule-action').on('click', function(e) {
                e.preventDefault();
                var postId = $(this).data('postid');
                $('.autobp-schedule-form-row').not('#autobp-schedule-form-' + postId).hide();
                $('tr.autobp-schedule-form-row').closest('tbody').find('tr').removeClass('autobp-selected-for-schedule');
                var targetFormRow = $('#autobp-schedule-form-' + postId);
                targetFormRow.toggle();
                if(targetFormRow.is(':visible')){
                    $(this).closest('tr').addClass('autobp-selected-for-schedule');
                    var now = new Date();
                    var month = (now.getMonth() + 1).toString().padStart(2, '0');
                    var day = now.getDate().toString().padStart(2, '0');
                    var year = now.getFullYear();
                    var hours = now.getHours().toString().padStart(2, '0');
                    var minutes = now.getMinutes().toString().padStart(2, '0');
                    targetFormRow.find('input[name="autobp_schedule_date"]').val(year + '-' + month + '-' + day);
                    targetFormRow.find('input[name="autobp_schedule_time"]').val(hours + ':' + minutes);
                } else {
                     $(this).closest('tr').removeClass('autobp-selected-for-schedule');
                }
            });
            $('.autobp-cancel-schedule').on('click', function(e) {
                e.preventDefault();
                var postId = $(this).data('postid');
                $('#autobp-schedule-form-' + postId).hide();
                $('#autobp-schedule-form-' + postId).closest('tbody').find('tr').removeClass('autobp-selected-for-schedule');
            });

            // Handler para Verificação de Plágio (novo)
            $('.autobp-check-plagiarism').on('click', function() {
                var postId = $(this).data('postid');
                var $button = $(this);
                var $resultSpan = $('#autobp-plagiarism-result-' + postId);
                var $spinner = $('#autobp-plagiarism-spinner-' + postId);

                $resultSpan.hide().html('');
                $spinner.addClass('is-active').css('display', 'inline-block'); // Usar display inline-block para o spinner
                $button.prop('disabled', true);

                $.ajax({
                    url: ajaxurl, // Variável global do WordPress
                    type: 'POST',
                    data: {
                        action: 'autobp_check_plagiarism', // Nossa action AJAX
                        post_id: postId,
                        _ajax_nonce: '<?php echo wp_create_nonce('autobp_check_plagiarism_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            var resultText = data.message;
                            var textColor = 'green'; // Cor padrão para sucesso sem cópias

                            if (data.count > 0) {
                                textColor = 'orange'; // Cor para quando cópias são encontradas
                                if (data.results_url) {
                                   resultText += ' <a href="' + data.results_url + '" target="_blank"><?php echo esc_js(__('Ver Relatório Completo', 'autoblogpro')); ?></a>';
                                }
                            }
                             if (data.cost_message) {
                                resultText += ' (' + data.cost_message + ')';
                            }
                            $resultSpan.html('<span style="color:' + textColor + ';">' + resultText + '</span>').show();
                        } else {
                            $resultSpan.html('<span style="color:red;">Erro: ' + response.data.message + '</span>').show();
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $resultSpan.html('<span style="color:red;">Erro AJAX: ' + textStatus + ' - ' + errorThrown + '</span>').show();
                    },
                    complete: function() {
                        $spinner.removeClass('is-active').hide();
                        $button.prop('disabled', false);
                    }
                });
            });

            // Handler para Busca de Imagens Pexels
            $('.autobp-fetch-pexels-images').on('click', function() {
                var postId = $(this).data('postid');
                var postTitle = $(this).data('posttitle'); // Usar o título do post como query inicial
                var $button = $(this);
                var $resultsDiv = $('#autobp-pexels-results-' + postId);
                var $spinner = $('#autobp-pexels-spinner-' + postId);

                $resultsDiv.hide().html('');
                $spinner.addClass('is-active').show();
                $button.prop('disabled', true);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'autobp_fetch_pexels_images',
                        post_id: postId,
                        query: postTitle, // Poderia ser refinado para usar keywords do post se disponíveis
                        _ajax_nonce: '<?php echo wp_create_nonce('autobp_fetch_pexels_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var images = response.data.images;
                            if (images && images.length > 0) {
                                var html = '<ul style="list-style:none; margin:0; padding:0; display:flex; flex-wrap:wrap;">';
                                images.forEach(function(img) {
                                    html += '<li style="margin:5px; border:1px solid #ddd; padding:5px; text-align:center; width: calc(20% - 10px); box-sizing: border-box;">'; // Adjust width for 5 per row
                                    html += '<img src="' + img.src.medium + '" alt="' + img.alt + '" style="max-width:100%; height:120px; object-fit:cover; display:block; margin-bottom:5px;">';
                                    html += '<button type="button" class="button button-small autobp-set-featured-image" data-postid="' + postId + '" data-imageid="' + img.id + '" data-imageurl="' + img.src.large2x + '" data-pexelsurl="'+img.pexels_url+'" data-photographer="'+img.photographer+'" data-alt="'+img.alt+'">';
                                    html += '<?php echo esc_js(__('Definir como Destacada', 'autoblogpro')); ?>';
                                    html += '</button>';
                                    html += '<br><small><a href="'+img.pexels_url+'" target="_blank" title="'+ escapeHtml(img.alt) +' Pexels ID: '+img.id+'">Pexels ID: '+img.id+'</a></small>';
                                    html += '</li>';
                                });
                                html += '</ul>';
                                $resultsDiv.html(html).show();
                            } else {
                                $resultsDiv.html('<p><?php echo esc_js(__('Nenhuma imagem encontrada para esta busca.', 'autoblogpro')); ?></p>').show();
                            }
                        } else {
                            $resultsDiv.html('<p style="color:red;">Error: ' + response.data.message + '</p>').show();
                        }
                    },
                    error: function() { $resultsDiv.html('<p style="color:red;"><?php echo esc_js(__('Erro AJAX ao buscar imagens.', 'autoblogpro')); ?></p>').show(); },
                    complete: function() {
                        $spinner.removeClass('is-active').hide();
                        $button.prop('disabled', false);
                    }
                });
            });

            // Função auxiliar para escapar HTML para atributos title
            function escapeHtml(unsafe) {
                return unsafe
                     .replace(/&/g, "&amp;")
                     .replace(/</g, "&lt;")
                     .replace(/>/g, "&gt;")
                     .replace(/"/g, "&quot;")
                     .replace(/'/g, "&#039;");
            }
            // O handler para .autobp-set-featured-image virá na próxima subtarefa
            $('body').on('click', '.autobp-set-featured-image', function() {
                var $setButton = $(this);
                var postId = $setButton.data('postid');
                var imagePexelsId = $setButton.data('imageid'); 
                var imageUrl = $setButton.data('imageurl'); 
                var pexelsPageUrl = $setButton.data('pexelsurl');
                var photographer = $setButton.data('photographer');
                var altText = $setButton.data('alt'); 

                var $resultsDiv = $('#autobp-pexels-results-' + postId);
                var $spinner = $('#autobp-pexels-spinner-' + postId); 

                $resultsDiv.find('.autobp-set-feedback').remove(); 
                var $feedbackSpan = $('<span class="autobp-set-feedback" style="margin-left:10px; display:block; clear:both; margin-top:5px;"></span>'); // display:block for new line
                // $setButton.after($feedbackSpan); // Place feedback after each button if needed, or a general one
                $resultsDiv.prepend($feedbackSpan); // Place general feedback at the top of results div
                
                $spinner.addClass('is-active').show();
                // Disable all "set featured image" buttons for this post
                $resultsDiv.find('.autobp-set-featured-image').prop('disabled', true);


                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'autobp_set_pexels_featured_image',
                        post_id: postId,
                        image_url: imageUrl,
                        image_pexels_id: imagePexelsId, 
                        image_alt: altText,
                        image_description: 'Photo by ' + photographer + ' from Pexels. URL: ' + pexelsPageUrl, 
                        _ajax_nonce: '<?php echo wp_create_nonce('autobp_set_pexels_featured_image_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $feedbackSpan.html('<span style="color:green;"><?php echo esc_js(__('Imagem definida!', 'autoblogpro')); ?></span>');
                            $resultsDiv.delay(2000).slideUp(); 
                        } else {
                            $feedbackSpan.html('<span style="color:red;">Error: ' + response.data.message + '</span>');
                            // Re-enable only the clicked button if that's the desired behavior, or all if general error
                            $resultsDiv.find('.autobp-set-featured-image').prop('disabled', false);
                        }
                    },
                    error: function() {
                        $feedbackSpan.html('<span style="color:red;"><?php echo esc_js(__('Erro AJAX ao definir imagem.', 'autoblogpro')); ?></span>');
                        $resultsDiv.find('.autobp-set-featured-image').prop('disabled', false);
                    },
                    complete: function() {
                        $spinner.removeClass('is-active').hide();
                    }
                });
            });

        });
        </script>
        <?php
        // REMOÇÃO DA SEÇÃO URL-MODE DE DENTRO DA LIBRARY PAGE - ELA ESTÁ NA GENERATE ARTICLES PAGE
    }


    /**
     * Renderiza a página de Logs do Sistema.
     *
     * Este método é responsável por exibir a interface de visualização dos logs do plugin.
     * Ele realiza as seguintes operações:
     * 1. Conexão com o Banco de Dados: Utiliza a variável global `$wpdb` para interagir
     *    com a tabela de logs personalizada (`{$wpdb->prefix}autoblogpro_logs`).
     * 2. Busca de Logs: Recupera as entradas de log do banco de dados, ordenadas pela
     *    data e hora (`log_timestamp`) em ordem decrescente.
     * 3. Paginação:
     *    - Define um número de logs a serem exibidos por página (`$logs_per_page`).
     *    - Calcula o offset para a consulta SQL com base na página atual (obtida do
     *      parâmetro de URL `paged`).
     *    - Utiliza a função `paginate_links()` do WordPress para gerar os links de navegação
     *      entre as páginas de logs.
     * 4. Exibição dos Logs:
     *    - Apresenta os logs em uma tabela HTML no estilo padrão do WordPress (`wp-list-table`).
     *    - As colunas da tabela são:
     *        - Timestamp (GMT): Data e hora do log.
     *        - Nível: Nível de severidade do log (INFO, WARNING, ERROR, DEBUG), com
     *          estilização CSS para diferenciar visualmente.
     *        - Mensagem: A mensagem principal do log.
     *        - Contexto: Dados adicionais associados ao log. Se o contexto for uma string
     *          JSON válida ou um array serializado, ele é formatado com `<pre>` para
     *          melhor legibilidade. Caso contrário, é exibido como texto simples.
     * 5. Funcionalidade "Limpar Todos os Logs":
     *    - Exibe um botão "Limpar Todos os Logs".
     *    - Este botão está dentro de um formulário que submete para a action
     *      `admin_post_autobp_clear_all_logs` (tratada em `autoblogpro.php`).
     *    - Inclui um nonce (`autobp_clear_all_logs_nonce`) para segurança e uma confirmação
     *      JavaScript antes de submeter a ação.
     * 6. Mensagens de Feedback: Exibe uma mensagem de sucesso se os logs forem limpos
     *    (baseado no query arg `autobp_log_message=cleared`).
     *
     * @since 0.2.0
     * @global wpdb $wpdb Objeto de acesso ao banco de dados do WordPress.
     * @access public
     * @static
     */
    public static function render_system_logs_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'autoblogpro_logs';

        // Paginação
        $logs_per_page = 50;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $offset = ( $current_page - 1 ) * $logs_per_page;

        // Busca logs
        $logs = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table_name} ORDER BY log_timestamp DESC LIMIT %d, %d",
            $offset,
            $logs_per_page
        ) );

        // Total de logs para paginação
        $total_logs = $wpdb->get_var( "SELECT COUNT(log_id) FROM {$table_name}" );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Logs do Sistema - AutoBlogPro', 'autoblogpro' ); ?></h1>

            <?php
            // Mensagem de feedback para limpeza de logs (se implementado)
            if ( isset( $_GET['autobp_log_message'] ) && $_GET['autobp_log_message'] === 'cleared' ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Todos os logs do sistema foram limpos.', 'autoblogpro' ) . '</p></div>';
            }
            ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="autobp_clear_all_logs">
                <?php wp_nonce_field( 'autobp_clear_all_logs_action', 'autobp_clear_all_logs_nonce' ); ?>
                <?php submit_button( __( 'Limpar Todos os Logs', 'autoblogpro' ), 'delete', 'clear_logs_button', false, array( 'onclick' => 'return confirm("' . esc_js( __( 'Tem certeza que deseja limpar todos os logs? Esta ação não pode ser desfeita.', 'autoblogpro' ) ) . '");' ) ); ?>
            </form>
            
            <hr/>

            <?php if ( ! empty( $logs ) ) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 180px;"><?php esc_html_e( 'Timestamp (GMT)', 'autoblogpro' ); ?></th>
                            <th scope="col" style="width: 100px;"><?php esc_html_e( 'Nível', 'autoblogpro' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Mensagem', 'autoblogpro' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Contexto', 'autoblogpro' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $logs as $log_entry ) : ?>
                            <tr>
                                <td><?php echo esc_html( $log_entry->log_timestamp ); ?></td>
                                <td>
                                    <span class="log-level log-level-<?php echo esc_attr( strtolower( $log_entry->log_level ) ); ?>">
                                        <?php echo esc_html( $log_entry->log_level ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( $log_entry->log_message ); ?></td>
                                <td>
                                    <?php
                                    if ( ! empty( $log_entry->log_context ) ) {
                                        $context_data = json_decode( $log_entry->log_context, true );
                                        if ( json_last_error() === JSON_ERROR_NONE && is_array( $context_data ) ) {
                                            echo '<pre>' . esc_html( print_r( $context_data, true ) ) . '</pre>';
                                        } else {
                                            // Tentar unserialize se não for JSON válido
                                            $unserialized_data = maybe_unserialize( $log_entry->log_context );
                                            if ( is_array( $unserialized_data ) || is_object( $unserialized_data ) ) {
                                                 echo '<pre>' . esc_html( print_r( $unserialized_data, true ) ) . '</pre>';
                                            } else {
                                                echo '<div style="max-height: 100px; overflow-y: auto; white-space: pre-wrap; word-break: break-all;">' . esc_html( $log_entry->log_context ) . '</div>';
                                            }
                                        }
                                    } else {
                                        echo '&mdash;';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                // Paginação
                $num_pages = ceil( $total_logs / $logs_per_page );
                if ( $num_pages > 1 ) {
                    echo '<div class="tablenav"><div class="tablenav-pages">';
                    echo paginate_links( array(
                        'base'      => admin_url( 'admin.php?page=autobp-system-logs%_%' ),
                        'format'    => '&paged=%#%',
                        'total'     => $num_pages,
                        'current'   => $current_page,
                        'prev_text' => __('&laquo; Anterior', 'autoblogpro'),
                        'next_text' => __('Próximo &raquo;', 'autoblogpro'),
                    ) );
                    echo '</div></div>';
                }
                ?>

            <?php else : ?>
                <p><?php esc_html_e( 'Nenhuma entrada de log encontrada.', 'autoblogpro' ); ?></p>
            <?php endif; ?>
        </div>
        <style>
            .log-level { padding: 2px 6px; border-radius: 3px; color: #fff; font-size: 0.9em; text-transform: uppercase; }
            .log-level-info { background-color: #0073aa; }
            .log-level-debug { background-color: #777; }
            .log-level-warning { background-color: #ffb900; color: #333; }
            .log-level-error { background-color: #d63638; }
        </style>
        <?php
    }
}

?>
