<?php
/**
 * Plugin Name:       AutoBlogPro
 * Plugin URI:        https://example.com/autoblogpro
 * Description:       Gera artigos de blog automaticamente com IA.
 * Version:           0.1.0
 * Author:            Seu Nome/Empresa Aqui
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       autoblogpro
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Define constantes do plugin.
 */
define( 'AUTOBP_VERSION', '0.1.0' );
// Caminho para o diretório do plugin.
define( 'AUTOBP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
// URL para o diretório do plugin.
define( 'AUTOBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
// Arquivo principal do plugin.
define( 'AUTOBP_PLUGIN_FILE', __FILE__ );

/**
 * A classe principal do plugin AutoBlogPro.
 *
 * Responsável por inicializar o plugin, carregar dependências,
 * registrar hooks e gerenciar a lógica central.
 *
 * @since 0.1.0
 */
if ( ! class_exists( 'AutoBlogPro' ) ) {
    /**
     * Classe principal AutoBlogPro.
     */
    class AutoBlogPro {

        /**
         * Versão atual do plugin.
         *
         * @since   0.1.0
         * @access  protected
         * @var     string      $version A versão atual do plugin.
         */
        protected $version;

        /**
         * A instância única da classe.
         * Padrão Singleton.
         *
         * @since   0.1.0
         * @access  private
         * @var     AutoBlogPro $instance A instância única da classe.
         */
        private static $instance = null;

        /**
         * Construtor privado para manter o padrão Singleton.
         *
         * Define a versão do plugin e adiciona os hooks iniciais.
         * - Carrega o textdomain para traduções.
         * - Registra o Custom Post Type (CPT) 'niche'.
         * - Carrega dependências administrativas (metaboxes, páginas de configurações).
         * - Registra actions para manipulação de posts (publicar, lixeira, agendar) da Biblioteca de Artigos.
         * - Registra action AJAX para verificação de plágio.
         * - Registra actions para processamento de URL base, limpeza de texto extraído, e reescrita de conteúdo.
         * - Registra action para regeneração de posts.
         * - Inclui e inicializa o `AutoBP_Log_Manager` (chamando `AutoBP_Log_Manager::init()`)
         *   para configurar o sistema de logs. (Desde 0.2.0)
         * - Registra a action `admin_post_autobp_generate_articles_auto` para o novo handler do formulário
         *   de "Modo Automático" na página "Gerar Artigos". (Desde 0.2.0)
         * - Registra a action `admin_post_autobp_clear_all_logs` para o handler de limpeza de logs
         *   na página "Logs do Sistema". (Desde 0.2.0)
         * - Registra a action `admin_post_autobp_restore_post` para restaurar posts da lixeira. (Desde 0.2.0)
         * - Registra a action `admin_post_autobp_delete_permanent_post` para excluir posts permanentemente. (Desde 0.2.0)
         * - Registra a action AJAX `wp_ajax_autobp_fetch_pexels_images` para buscar imagens na Pexels. (Desde 0.2.0)
         * - Registra a action AJAX `wp_ajax_autobp_set_pexels_featured_image` para definir uma imagem da Pexels como destacada. (Desde 0.2.0)
         * - Registra a action `wp_head` para adicionar o Schema Markup de Artigo (`add_article_schema_markup`). (Desde 0.2.0)
         * - Carrega dependências administrativas, incluindo a nova `AutoBP_SEO_Suggestions_Meta_Box`. (Desde 0.2.0)
         *
         * @since   0.1.0 (Atualizado em 0.2.0 com novos hooks e funcionalidades)
         * @access  private
         */
        private function __construct() {
            $this->version = AUTOBP_VERSION;

            // Incluir e inicializar o Log Manager
            if ( file_exists( AUTOBP_PLUGIN_DIR . 'includes/class-autobp-log-manager.php' ) ) {
                require_once AUTOBP_PLUGIN_DIR . 'includes/class-autobp-log-manager.php';
                if ( class_exists( 'AutoBP_Log_Manager' ) ) {
                    AutoBP_Log_Manager::init(); // Inicializa o gerenciador de logs.
                }
            }
            
            // Ações de inicialização aqui
            add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
            add_action( 'init', array( $this, 'register_niche_cpt' ) );
            add_action( 'admin_init', array( $this, 'load_admin_dependencies' ) );
            add_action( 'admin_post_autobp_publish_post', array( $this, 'handle_publish_post_action' ) );
            add_action( 'admin_post_autobp_trash_post', array( $this, 'handle_trash_post_action' ) );
            add_action( 'admin_post_autobp_schedule_post', array( $this, 'handle_schedule_post_action' ) );
            add_action( 'wp_ajax_autobp_check_plagiarism', array( $this, 'handle_ajax_check_plagiarism' ) );
            add_action( 'admin_post_autobp_process_url_base', array( $this, 'handle_process_url_base_action' ) );
            add_action( 'admin_post_autobp_clear_extracted_text', array( $this, 'handle_clear_extracted_text_action' ) );
            add_action( 'admin_post_autobp_rewrite_url_content', array( $this, 'handle_rewrite_url_content_action' ) );
            add_action( 'admin_post_autobp_regenerate_post', array( $this, 'handle_regenerate_post_action' ) );
            add_action( 'admin_post_autobp_clear_all_logs', array( $this, 'handle_clear_all_logs_action' ) );
            add_action( 'admin_post_autobp_generate_articles_auto', array( $this, 'handle_generate_articles_auto_action' ) );
            add_action( 'wp_ajax_autobp_fetch_pexels_images', array( $this, 'handle_ajax_fetch_pexels_images' ) );
            add_action( 'wp_ajax_autobp_set_pexels_featured_image', array( $this, 'handle_ajax_set_pexels_featured_image' ) );
            add_action( 'admin_post_autobp_restore_post', array( $this, 'handle_restore_post_action' ) );
            add_action( 'admin_post_autobp_delete_permanent_post', array( $this, 'handle_delete_permanent_post_action' ) );
            add_action( 'wp_head', array( $this, 'add_article_schema_markup' ) );
            add_action( 'admin_post_autobp_reset_openai_token_count', array( $this, 'handle_reset_openai_token_count_action' ) );
        }

        /**
         * Adiciona ao contador de uso de tokens da API OpenAI.
         * @param int $tokens_used Número de tokens a adicionar.
         * @since 0.2.0
         */
        public static function add_to_openai_token_usage( $tokens_used ) {
            if ( ! is_numeric( $tokens_used ) || $tokens_used <= 0 ) {
                return;
            }
            $current_usage = get_option( 'autobp_openai_total_tokens_used', 0 );
            $new_usage = $current_usage + (int) $tokens_used;
            update_option( 'autobp_openai_total_tokens_used', $new_usage );
            // Verifica se o Log_Manager está disponível para evitar erros fatais se chamado muito cedo ou em contextos limitados.
            if (class_exists('AutoBP_Log_Manager')) {
                 AutoBP_Log_Manager::debug( "Tokens OpenAI adicionados ao total: {$tokens_used}. Novo total estimado: {$new_usage}." );
            }
        }

        /**
         * Carrega as dependências da área administrativa do plugin.
         *
         * Inclui arquivos necessários para metaboxes e páginas de configurações
         * e inicializa as respectivas classes.
         * Chamado no hook 'admin_init'.
         *
         * @since 0.1.0
         * @access public
         */
        public function load_admin_dependencies() {
            require_once AUTOBP_PLUGIN_DIR . 'admin/meta-boxes/class-niche-category-meta-box.php';
            Niche_Category_Meta_Box::init();

            require_once AUTOBP_PLUGIN_DIR . 'admin/class-autobp-settings-page.php';
            AutoBP_Settings_Page::init();

            require_once AUTOBP_PLUGIN_DIR . 'admin/meta-boxes/class-autobp-seo-suggestions-meta-box.php';
            AutoBP_SEO_Suggestions_Meta_Box::init();
        }

        /**
         * Retorna a instância única da classe (Singleton).
         *
         * @since   0.1.0
         * @access  public
         * @static
         * @return  AutoBlogPro A instância única da classe.
         */
        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        /**
         * Carrega o textdomain do plugin para tradução.
         *
         * @since   0.1.0
         * @access  public
         */
        public function load_plugin_textdomain() {
            load_plugin_textdomain(
                'autoblogpro',
                false,
                dirname( plugin_basename( AUTOBP_PLUGIN_FILE ) ) . '/languages/'
            );
        }

        /**
         * Lógica a ser executada na ativação do plugin.
         *
         * - Cria a tabela de logs `{$wpdb->prefix}autoblogpro_logs` (se não existir)
         *   utilizando a função `dbDelta()`. A estrutura da tabela inclui `log_id` (PK, AI),
         *   `log_timestamp`, `log_level`, `log_message` e `log_context`.
         * - Adiciona ou atualiza a opção `autobp_log_table_version` no banco de dados do WordPress
         *   para versionamento e possíveis futuras atualizações da estrutura da tabela de logs.
         * - Exemplo comentado de como registrar CPTs e fazer flush de regras de reescrita,
         *   que não é executado por padrão aqui, mas serve como referência.
         *
         * @since   0.1.0 (Tabela de logs e versionamento adicionados na 0.2.0)
         * @global  wpdb $wpdb Objeto de acesso ao banco de dados do WordPress.
         * @access  public
         * @static
         */
        public static function activate() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'autoblogpro_logs';
            $charset_collate = $wpdb->get_charset_collate();

            // SQL para criar a tabela de logs.
            // dbDelta requer que as chaves primárias estejam definidas explicitamente.
            // Índices adicionais (KEY) são definidos para log_level e log_timestamp para otimizar consultas.
            $sql = "CREATE TABLE $table_name (
                log_id BIGINT(20) NOT NULL AUTO_INCREMENT,
                log_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                log_level VARCHAR(20) NOT NULL,
                log_message TEXT NOT NULL,
                log_context LONGTEXT,
                PRIMARY KEY  (log_id),
                KEY idx_log_level (log_level),
                KEY idx_log_timestamp (log_timestamp)
            ) $charset_collate;";

            // dbDelta precisa do arquivo upgrade.php
            if ( ! function_exists( 'dbDelta' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            }
            dbDelta( $sql ); // Executa a criação/atualização da tabela.

            // Adiciona/atualiza a versão da tabela de logs.
            // Isso é útil se precisarmos modificar a estrutura da tabela no futuro.
            // Poderíamos verificar a versão existente e aplicar alterações se necessário.
            if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
                 update_option( 'autobp_log_table_version', '1.0' ); // Atualiza ou adiciona a opção.
            }
            // self::register_niche_cpt(); // Garante que o CPT esteja registrado para o flush
            // flush_rewrite_rules(); // Importante após registrar CPTs/taxonomias pela primeira vez
        }

        /**
         * Registra o Custom Post Type (CPT) 'Nicho'.
         *
         * Define os rótulos, argumentos e registra o CPT 'niche'.
         * Chamado no hook 'init'.
         *
         * @since   0.1.0
         * @access  public
         */
        public function register_niche_cpt() {
            $labels = array(
                'name'                  => _x( 'Nichos', 'Post type general name', 'autoblogpro' ),
                        'singular_name'         => _x( 'Nicho', 'Post type singular name', 'autoblogpro' ),
                        'menu_name'             => _x( 'Nichos', 'Admin Menu text', 'autoblogpro' ),
                        'name_admin_bar'        => _x( 'Nicho', 'Add New on Toolbar', 'autoblogpro' ),
                        'add_new'               => __( 'Adicionar Novo', 'autoblogpro' ),
                        'add_new_item'          => __( 'Adicionar Novo Nicho', 'autoblogpro' ),
                        'new_item'              => __( 'Novo Nicho', 'autoblogpro' ),
                        'edit_item'             => __( 'Editar Nicho', 'autoblogpro' ),
                        'view_item'             => __( 'Ver Nicho', 'autoblogpro' ),
                        'all_items'             => __( 'Todos os Nichos', 'autoblogpro' ),
                        'search_items'          => __( 'Pesquisar Nichos', 'autoblogpro' ),
                        'parent_item_colon'     => __( 'Nicho Pai:', 'autoblogpro' ),
                        'not_found'             => __( 'Nenhum nicho encontrado.', 'autoblogpro' ),
                        'not_found_in_trash'    => __( 'Nenhum nicho encontrado na lixeira.', 'autoblogpro' ),
                        'featured_image'        => _x( 'Imagem Destacada do Nicho', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'autoblogpro' ),
                        'set_featured_image'    => _x( 'Definir imagem destacada', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'autoblogpro' ),
                        'remove_featured_image' => _x( 'Remover imagem destacada', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'autoblogpro' ),
                        'use_featured_image'    => _x( 'Usar como imagem destacada', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'autoblogpro' ),
                        'archives'              => _x( 'Arquivos de Nichos', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'autoblogpro' ),
                        'insert_into_item'      => _x( 'Inserir no nicho', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'autoblogpro' ),
                        'uploaded_to_this_item' => _x( 'Enviado para este nicho', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'autoblogpro' ),
                        'filter_items_list'     => _x( 'Filtrar lista de nichos', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'autoblogpro' ),
                        'items_list_navigation' => _x( 'Navegação na lista de nichos', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'autoblogpro' ),
                        'items_list'            => _x( 'Lista de nichos', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'autoblogpro' ),
                    );

                    $args = array(
                        'labels'             => $labels,
                        'public'             => true,
                        'has_archive'        => true,
                        'supports'           => array( 'title', 'editor' ),
                        'rewrite'            => array( 'slug' => 'nicho' ), // slug de reescrita
                        'menu_icon'          => 'dashicons-archive',
                        'show_in_rest'       => true,
                        'publicly_queryable' => true,
                        'show_ui'            => true,
                        'show_in_menu'       => true,
                        'query_var'          => true,
                        'capability_type'    => 'post',
                        'hierarchical'       => false,
                        'menu_position'      => null,
                    );

                    register_post_type( 'niche', $args ); // 'niche' como slug do CPT
                }

                /**
                 * Lógica a ser executada na desativação do plugin.
                 *
                 * Pode ser usado para limpar configurações, remover tabelas, etc.
                 *
                 * @since   0.1.0
                 * @access  public
                 * @static
                 */
                public static function deactivate() {
                    // Código a ser executado na desativação
                    // flush_rewrite_rules(); // Opcional, mas pode ajudar a limpar
                }

        /**
         * Manipula a ação de publicar um post ('autobp_publish_post') acionada a partir da Biblioteca de Artigos.
         *
         * Registrado no hook `admin_post_autobp_publish_post`.
         * Valida `post_id`, nonce e permissões. Publica o post via `wp_update_post()`.
         * Redireciona para a Biblioteca de Artigos com feedback.
         *
         * @since 0.1.0
         * @access public
         */
        public function handle_publish_post_action() {
            $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
            $user_id = get_current_user_id();
            AutoBP_Log_Manager::info( 'Tentativa de publicar post.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
            
            $redirect_url = admin_url( 'admin.php?page=autobp-article-library' );

            if ( ! $post_id ) {
                AutoBP_Log_Manager::warning( 'Falha ao publicar post: ID do post inválido.', array( 'post_id_received' => isset( $_GET['post_id'] ) ? $_GET['post_id'] : 'N/A', 'user_id' => $user_id ) );
                $redirect_url = add_query_arg( array( 'autobp_message' => 'invalid_post_id', 'post_id' => 0 ), $redirect_url );
                wp_safe_redirect( $redirect_url );
                exit;
            }

            check_admin_referer( 'autobp_publish_post_' . $post_id );

            if ( ! current_user_can( 'publish_post', $post_id ) ) {
                AutoBP_Log_Manager::warning( 'Falha ao publicar post: Permissão negada.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                $redirect_url = add_query_arg( array( 'autobp_message' => 'publish_permission_denied', 'post_id' => $post_id ), $redirect_url );
                wp_safe_redirect( $redirect_url );
                exit;
            }

            $updated_post_result = wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ), true );

            if ( is_wp_error( $updated_post_result ) ) {
                AutoBP_Log_Manager::error( 'Falha ao publicar post (wp_update_post): ' . $updated_post_result->get_error_message(), array( 'post_id' => $post_id, 'user_id' => $user_id, 'error_code' => $updated_post_result->get_error_code() ) );
                $redirect_url = add_query_arg( array( 'autobp_message' => 'publish_failed', 'post_id' => $post_id ), $redirect_url );
            } else {
                AutoBP_Log_Manager::info( 'Post publicado com sucesso.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                $redirect_url = add_query_arg( array( 'autobp_message' => 'post_published', 'post_id' => $post_id ), $redirect_url );
            }

            wp_safe_redirect( $redirect_url ); 
            exit; 
        }

        /**
         * Manipula a ação de mover um post para a lixeira (`autobp_trash_post`) a partir da Biblioteca de Artigos.
         *
         * Registrado no hook `admin_post_autobp_trash_post`.
         * Valida `post_id`, nonce e permissões. Move o post para a lixeira via `wp_trash_post()`.
         * Redireciona para a Biblioteca de Artigos com feedback.
         *
         * @since 0.1.0
         * @access public
         */
        public function handle_trash_post_action() {
            $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
            $user_id = get_current_user_id();
            AutoBP_Log_Manager::info( 'Tentativa de mover post para a lixeira.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
            
            $redirect_url = admin_url( 'admin.php?page=autobp-article-library&post_id=' . $post_id );

            if ( ! $post_id ) { 
                AutoBP_Log_Manager::warning( 'Falha ao mover post para lixeira: ID do post inválido.', array( 'post_id_received' => isset( $_GET['post_id'] ) ? $_GET['post_id'] : 'N/A', 'user_id' => $user_id ) );
                $redirect_url = add_query_arg( array( 'autobp_message' => 'invalid_post_id'), admin_url( 'admin.php?page=autobp-article-library&post_id=0' ) );
                wp_safe_redirect( $redirect_url );
                exit;
            }

            check_admin_referer( 'autobp_trash_post_' . $post_id );

            if ( ! current_user_can( 'delete_post', $post_id ) ) {
                AutoBP_Log_Manager::warning( 'Falha ao mover post para lixeira: Permissão negada.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                $redirect_url = add_query_arg( array( 'autobp_message' => 'trash_permission_denied' ), $redirect_url );
                wp_safe_redirect( $redirect_url );
                exit;
            }

            $result = wp_trash_post( $post_id );

            if ( false === $result ) {
                AutoBP_Log_Manager::error( 'Falha ao mover post para lixeira (wp_trash_post retornou false).', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                $redirect_url = add_query_arg( array( 'autobp_message' => 'trash_failed' ), $redirect_url );
            } else {
                AutoBP_Log_Manager::info( 'Post movido para a lixeira com sucesso.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                $redirect_url = add_query_arg( array( 'autobp_message' => 'post_trashed' ), $redirect_url );
            }
            wp_safe_redirect( $redirect_url );
            exit;
        }

        /**
         * Manipula a ação de agendar um post (`autobp_schedule_post`) a partir da Biblioteca de Artigos.
         *
         * Registrado no hook `admin_post_autobp_schedule_post`.
         * Valida `post_id` (de `$_POST`), nonce, permissões e data/hora de agendamento.
         * A data/hora fornecida (local do site) é convertida para GMT.
         * Verifica se a data de agendamento está no futuro.
         * Atualiza o post com `post_status`='future' e as datas corretas usando `wp_update_post()`.
         * Redireciona para a Biblioteca de Artigos com feedback.
         *
         * @since 0.1.0
         * @access public
         */
        public function handle_schedule_post_action() {
            $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
            $user_id = get_current_user_id();
            $schedule_date_str = isset( $_POST['autobp_schedule_date'] ) ? sanitize_text_field( $_POST['autobp_schedule_date'] ) : '';
            $schedule_time_str = isset( $_POST['autobp_schedule_time'] ) ? sanitize_text_field( $_POST['autobp_schedule_time'] ) : '';
            AutoBP_Log_Manager::info( 'Tentativa de agendar post.', array( 'post_id' => $post_id, 'user_id' => $user_id, 'schedule_date' => $schedule_date_str, 'schedule_time' => $schedule_time_str ) );

            $redirect_url = admin_url( 'admin.php?page=autobp-article-library&post_id=' . $post_id );

            if ( ! $post_id ) {
                AutoBP_Log_Manager::warning( 'Falha ao agendar post: ID do post inválido.', array( 'post_id_received' => isset( $_POST['post_id'] ) ? $_POST['post_id'] : 'N/A', 'user_id' => $user_id ) );
                $redirect_url = add_query_arg( array( 'autobp_message' => 'invalid_post_id'), admin_url( 'admin.php?page=autobp-article-library&post_id=0' ) );
                wp_safe_redirect( $redirect_url );
                exit;
            }

            check_admin_referer( 'autobp_schedule_post_' . $post_id, '_wpnonce_autobp_schedule' );

            if ( ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'publish_posts' ) ) {
                AutoBP_Log_Manager::warning( 'Falha ao agendar post: Permissão negada.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                $redirect_url = add_query_arg( array( 'autobp_message' => 'schedule_permission_denied' ), $redirect_url );
                wp_safe_redirect( $redirect_url );
                exit;
            }
            
            $datetime_string = $schedule_date_str . ' ' . $schedule_time_str;
            $local_datetime = new DateTime( $datetime_string, wp_timezone() ); 
            $timestamp_gmt = $local_datetime->getTimestamp(); 
            $post_date_gmt = gmdate( 'Y-m-d H:i:s', $timestamp_gmt );
            $post_date_local = get_date_from_gmt( $post_date_gmt );

            if ( false === $timestamp_gmt || $timestamp_gmt < current_time( 'timestamp', true ) ) {
                 AutoBP_Log_Manager::warning( 'Falha ao agendar post: Data ou hora inválida (não é no futuro).', array( 'post_id' => $post_id, 'user_id' => $user_id, 'datetime_string' => $datetime_string, 'timestamp_gmt' => $timestamp_gmt ) );
                 $redirect_url = add_query_arg( array( 'autobp_message' => 'schedule_invalid_date' ), $redirect_url );
                 wp_safe_redirect( $redirect_url );
                 exit;
            }

            $post_data = array(
                'ID'            => $post_id,
                'post_status'   => 'future',
                'post_date'     => $post_date_local, 
                'post_date_gmt' => $post_date_gmt,   
                'edit_date'     => true,             
            );

            $result = wp_update_post( $post_data, true );

            if ( is_wp_error( $result ) ) {
                AutoBP_Log_Manager::error( 'Falha ao agendar post (wp_update_post): ' . $result->get_error_message(), array( 'post_id' => $post_id, 'user_id' => $user_id, 'post_data' => $post_data, 'error_code' => $result->get_error_code() ) );
                $redirect_url = add_query_arg( array( 'autobp_message' => 'schedule_failed' ), $redirect_url );
            } else {
                $formatted_schedule_date = date_i18n( get_option('date_format') . ' @ ' . get_option('time_format'), strtotime($post_date_local) );
                AutoBP_Log_Manager::info( 'Post agendado com sucesso.', array( 'post_id' => $post_id, 'user_id' => $user_id, 'scheduled_to' => $formatted_schedule_date ) );
                $redirect_url = add_query_arg( array( 'autobp_message' => 'post_scheduled', 'scheduled_date' => urlencode($formatted_schedule_date) ), $redirect_url );
            }
            wp_safe_redirect( $redirect_url );
            exit;
        }

        /**
         * Manipula a requisição AJAX para verificar plágio de um post (`autobp_check_plagiarism`)
         * usando a API Copyscape.
         *
         * Registrado no hook `wp_ajax_autobp_check_plagiarism`.
         * Valida nonce, `post_id` e permissões. Obtém o conteúdo do post,
         * recupera credenciais Copyscape, e chama `AutoBP_Copyscape_Checker`.
         * Retorna uma resposta JSON com o resultado ou erro.
         * Salva o resultado da verificação e a data como metadados do post.
         *
         * @since 0.1.0
         */
        public function handle_ajax_check_plagiarism() {
            check_ajax_referer( 'autobp_check_plagiarism_nonce', '_ajax_nonce' );
            $post_id_input = isset( $_POST['post_id'] ) ? $_POST['post_id'] : null;
            $user_id = get_current_user_id();
            AutoBP_Log_Manager::info( 'Iniciando verificação de plágio (AJAX).', array( 'post_id_input' => $post_id_input, 'user_id' => $user_id ) );

            if ( ! is_numeric( $post_id_input ) ) {
                AutoBP_Log_Manager::error( 'Verificação de plágio falhou: ID do post inválido.', array( 'post_id_input' => $post_id_input, 'user_id' => $user_id ) );
                wp_send_json_error( array( 'message' => __( 'ID do post inválido.', 'autoblogpro' ) ) );
            }
            $post_id = intval( $post_id_input );

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                AutoBP_Log_Manager::warning( 'Verificação de plágio falhou: Permissão negada.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                wp_send_json_error( array( 'message' => __( 'Você não tem permissão para verificar este post.', 'autoblogpro' ) ) );
            }

            $post_to_check = get_post( $post_id );
            if ( ! $post_to_check ) {
                AutoBP_Log_Manager::error( 'Verificação de plágio falhou: Post não encontrado.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                wp_send_json_error( array( 'message' => __( 'Post não encontrado.', 'autoblogpro' ) ) );
            }

            $content_to_check = wp_strip_all_tags( $post_to_check->post_content );
            $content_to_check = trim( preg_replace( '/\s+/', ' ', $content_to_check ) );

            if ( empty( $content_to_check ) ) {
                AutoBP_Log_Manager::info( 'Verificação de plágio: Conteúdo do post está vazio.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                wp_send_json_error( array( 'message' => __( 'O conteúdo do post está vazio. Não há nada para verificar.', 'autoblogpro' ) ) );
            }

            $username = get_option( 'autobp_copyscape_username' );
            $api_key = get_option( 'autobp_copyscape_api_key' );

            if ( empty( $username ) || empty( $api_key ) ) {
                AutoBP_Log_Manager::warning( 'Verificação de plágio falhou: Credenciais Copyscape não configuradas.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                wp_send_json_error( array( 'message' => __( 'Credenciais da API Copyscape não configuradas nas Configurações do AutoBlogPro.', 'autoblogpro' ) ) );
            }

            if ( ! class_exists( 'AutoBP_Copyscape_Checker' ) ) {
                require_once AUTOBP_PLUGIN_DIR . 'includes/class-autobp-copyscape-checker.php';
            }

            $checker = new AutoBP_Copyscape_Checker( $username, $api_key );
            $checker->set_text( $content_to_check );
            $result = $checker->check();

            if ( is_wp_error( $result ) ) {
                AutoBP_Log_Manager::error( 'Erro na chamada ao Copyscape Checker: ' . $result->get_error_message(), array( 'post_id' => $post_id, 'user_id' => $user_id, 'error_code' => $result->get_error_code() ) );
                wp_send_json_error( array( 'message' => $result->get_error_message() ) );
            } else {
                $cost_message = sprintf( 
                    esc_html__( 'Custo: %s créditos.', 'autoblogpro' ), 
                    number_format_i18n( $result['cost'], 2 )
                );
                AutoBP_Log_Manager::info( 'Verificação de plágio Copyscape concluída.', array( 'post_id' => $post_id, 'user_id' => $user_id, 'result_count' => $result['count'], 'cost' => $result['cost'] ) );
                
                $data_to_send = array(
                    'count'        => $result['count'],
                    'message'      => '',
                    'results_url'  => isset($result['allresultsurl']) ? esc_url($result['allresultsurl']) : (isset($result['results'][0]['viewurl']) ? esc_url($result['results'][0]['viewurl']) : ''), 
                    'cost_message' => $cost_message
                );

                if ( $result['count'] > 0 ) {
                    $data_to_send['message'] = sprintf( _n( '%d resultado de plágio encontrado.', '%d resultados de plágio encontrados.', $result['count'], 'autoblogpro' ), $result['count'] );
                } else {
                    $data_to_send['message'] = __( 'Nenhuma cópia significativa encontrada.', 'autoblogpro' );
                }
                
                update_post_meta( $post_id, '_autobp_copyscape_last_check_result', $result );
                update_post_meta( $post_id, '_autobp_copyscape_last_check_date', current_time('mysql') );

                wp_send_json_success( $data_to_send );
            }
        }

        /**
         * Manipula a submissão do formulário para processar uma URL base (`autobp_process_url_base`).
         *
         * Registrado no hook `admin_post_autobp_process_url_base`.
         * 1. Verifica nonce e permissões (`manage_options`).
         * 2. Valida a URL de origem.
         * 3. Instancia `AutoBP_URL_Content_Processor` e chama `fetch_content()` e `extract_main_text()`.
         * 4. Se a extração de texto for bem-sucedida, chama `analyze_text_content()` para obter
         *    palavras-chave e tópicos da API OpenAI (se a chave da API estiver configurada).
         * 5. Em seguida, chama `fetch_additional_insights()` para obter mais ideias com base nos
         *    dados analisados e um snippet do texto.
         * 6. Armazena todos os dados coletados (texto extraído, palavras-chave, tópicos, insights,
         *    e mensagens de status da análise/insights) em um transient (`autobp_extracted_data_{user_id}`).
         * 7. Redireciona de volta para a página "Gerar Artigos" (`autobp-generate-articles`) com
         *    mensagens de feedback e a URL submetida (para repopular o campo).
         *
         * @since 0.1.0
         */
        public function handle_process_url_base_action() {
            check_admin_referer( 'autobp_process_url_base_action', 'autobp_process_url_base_nonce' );
            $user_id = get_current_user_id();
            if ( ! current_user_can( 'manage_options' ) ) { 
                AutoBP_Log_Manager::warning( 'Processamento de URL base falhou: Permissão negada.', array( 'user_id' => $user_id ) );
                wp_die( __( 'Você não tem permissão para realizar esta ação.', 'autoblogpro' ) );
            }

            $source_url = isset( $_POST['autobp_source_url'] ) ? esc_url_raw( $_POST['autobp_source_url'] ) : '';
            AutoBP_Log_Manager::info( 'Iniciando processamento de URL base.', array( 'url' => $source_url, 'user_id' => $user_id ) );
            
            $generate_page_slug = 'autobp-generate-articles'; 
            $redirect_url = admin_url( 'admin.php?page=' . $generate_page_slug . '&tab=url_mode' ); // Added &tab=url_mode
            $redirect_url = add_query_arg( array( 'autobp_source_url_submitted' => urlencode($source_url) ), $redirect_url );

            if ( empty( $source_url ) ) {
                AutoBP_Log_Manager::info( 'Processamento de URL base: URL não fornecida.', array( 'user_id' => $user_id ) );
                wp_safe_redirect( add_query_arg( array( 'autobp_url_message' => 'url_missing' ), $redirect_url . '#url-mode-section' ) ); // redirect_url already has tab
                exit;
            }

            if ( ! class_exists( 'AutoBP_URL_Content_Processor' ) ) {
                require_once AUTOBP_PLUGIN_DIR . 'includes/class-autobp-url-content-processor.php';
            }

            try {
                $processor = new AutoBP_URL_Content_Processor( $source_url );
                $fetch_result = $processor->fetch_content();

                if ( is_wp_error( $fetch_result ) ) {
                    AutoBP_Log_Manager::error( 'Falha ao buscar conteúdo da URL: ' . $fetch_result->get_error_message(), array( 'url' => $source_url, 'user_id' => $user_id, 'error_code' => $fetch_result->get_error_code() ) );
                    wp_safe_redirect( add_query_arg( array( 'autobp_url_message' => $fetch_result->get_error_code() ), $redirect_url . '#url-mode-section' ) ); 
                    exit;
                }
                AutoBP_Log_Manager::info( 'Conteúdo da URL buscado com sucesso.', array( 'url' => $source_url, 'user_id' => $user_id ) );

                $extracted_text = $processor->extract_main_text();
                if ( is_wp_error( $extracted_text ) ) {
                    AutoBP_Log_Manager::error( 'Falha ao extrair texto principal da URL: ' . $extracted_text->get_error_message(), array( 'url' => $source_url, 'user_id' => $user_id, 'error_code' => $extracted_text->get_error_code() ) );
                    wp_safe_redirect( add_query_arg( array( 'autobp_url_message' => $extracted_text->get_error_code() ), $redirect_url . '#url-mode-section' ) ); 
                    exit;
                }
                AutoBP_Log_Manager::info( 'Texto principal extraído com sucesso.', array( 'url' => $source_url, 'user_id' => $user_id, 'text_length' => mb_strlen($extracted_text) ) );
                
                $openai_api_key = get_option( 'autobp_openai_api_key' );
                $analysis_message = '';
                $insights_message = '';
                $keywords = array();
                $topics = array();
                $additional_insights = array();
                $extraction_success_status = 'extraction_success'; 

                if ( !empty( $openai_api_key ) && !empty( $extracted_text ) ) {
                    AutoBP_Log_Manager::info( 'Iniciando análise semântica do texto extraído.', array( 'url' => $source_url, 'user_id' => $user_id ) );
                    $analysis_result = $processor->analyze_text_content( $extracted_text, $openai_api_key );
                    if ( is_wp_error( $analysis_result ) ) {
                        $analysis_message = sprintf( __('Falha na análise semântica: %s', 'autoblogpro'), $analysis_result->get_error_message() );
                        AutoBP_Log_Manager::error( 'Análise semântica falhou: ' . $analysis_result->get_error_message(), array( 'url' => $source_url, 'user_id' => $user_id, 'error_code' => $analysis_result->get_error_code() ) );
                    } elseif ( $analysis_result === true ) {
                        $keywords = $processor->get_identified_keywords();
                        $topics = $processor->get_identified_topics();
                        $analysis_message = __('Análise semântica concluída.', 'autoblogpro');
                        AutoBP_Log_Manager::info( 'Análise semântica concluída.', array( 'url' => $source_url, 'user_id' => $user_id, 'keywords_found' => count($keywords), 'topics_found' => count($topics) ) );

                        AutoBP_Log_Manager::info( 'Iniciando busca por insights adicionais.', array( 'url' => $source_url, 'user_id' => $user_id ) );
                        $text_snippet = mb_substr( $extracted_text, 0, 350 ); 
                        $insights_result = $processor->fetch_additional_insights( $keywords, $topics, $text_snippet, $openai_api_key );
                        if ( is_wp_error( $insights_result ) ) {
                            $insights_message = sprintf( __('Falha na busca por insights: %s', 'autoblogpro'), $insights_result->get_error_message() );
                             AutoBP_Log_Manager::error( 'Busca por insights adicionais falhou: ' . $insights_result->get_error_message(), array( 'url' => $source_url, 'user_id' => $user_id, 'error_code' => $insights_result->get_error_code() ) );
                        } elseif ( $insights_result === true ) {
                            $additional_insights = $processor->get_additional_insights();
                            $insights_message = __('Pesquisa de insights adicionais concluída.', 'autoblogpro');
                            AutoBP_Log_Manager::info( 'Busca por insights adicionais concluída.', array( 'url' => $source_url, 'user_id' => $user_id, 'insights_found' => count($additional_insights) ) );
                        }
                    }
                } elseif ( empty($openai_api_key) ) {
                    $analysis_message = __('Análise semântica e busca por insights puladas: Chave da API OpenAI não configurada.', 'autoblogpro');
                    $insights_message = $analysis_message; 
                    AutoBP_Log_Manager::info( 'Análise e insights pulados (API Key não configurada).', array( 'url' => $source_url, 'user_id' => $user_id ) );
                } else { 
                    $analysis_message = __('Análise semântica e busca por insights puladas: Conteúdo extraído está vazio.', 'autoblogpro');
                    $insights_message = $analysis_message;
                    AutoBP_Log_Manager::info( 'Análise e insights pulados (Conteúdo extraído vazio).', array( 'url' => $source_url, 'user_id' => $user_id ) );
                }
                
                $data_to_store = array(
                   'text'                => $extracted_text,
                   'keywords'            => $keywords,
                   'topics'              => $topics,
                   'analysis_message'    => $analysis_message,
                   'additional_insights' => $additional_insights,
                   'insights_message'    => $insights_message,
                   'source_url'          => $source_url, // Adicionar a URL original para referência futura
                );
                
                set_transient( 'autobp_extracted_data_' . $user_id, $data_to_store, HOUR_IN_SECONDS );
                AutoBP_Log_Manager::info( 'Dados extraídos da URL armazenados no transient.', array( 'url' => $source_url, 'user_id' => $user_id, 'transient_key' => 'autobp_extracted_data_' . $user_id ) );
                wp_safe_redirect( add_query_arg( array( 'autobp_url_message' => $extraction_success_status ), $redirect_url . '#url-mode-section' ) ); 
                exit;

            } catch ( InvalidArgumentException $e ) {
                 AutoBP_Log_Manager::error( 'Exceção InvalidArgumentException no processamento de URL: ' . $e->getMessage(), array( 'url' => $source_url, 'user_id' => $user_id, 'exception_type' => 'InvalidArgumentException' ) );
                 wp_safe_redirect( add_query_arg( array( 'autobp_url_message' => 'invalid_url_format' ), $redirect_url . '#url-mode-section' ) ); 
                 exit;
            } catch ( Exception $e ) { 
                 AutoBP_Log_Manager::error( 'Exceção genérica no processamento de URL: ' . $e->getMessage(), array( 'url' => $source_url, 'user_id' => $user_id, 'exception_type' => 'Exception' ) );
                 wp_safe_redirect( add_query_arg( array( 'autobp_url_message' => 'unknown_extraction_error' ), $redirect_url . '#url-mode-section' ) ); 
                 exit;
            }
        }

        /**
         * Manipula a ação de limpar o conteúdo extraído (`autobp_clear_extracted_text`)
         * armazenado no transient do usuário.
         *
         * Registrado no hook `admin_post_autobp_clear_extracted_text`.
         * Verifica nonce e permissões (`manage_options`). Deleta o transient 
         * `autobp_extracted_data_{user_id}` e redireciona para a página "Gerar Artigos"
         * com uma mensagem de confirmação.
         *
         * @since 0.1.0
         */
        public function handle_clear_extracted_text_action() {
            check_admin_referer( 'autobp_clear_extracted_text_nonce', '_wpnonce' );
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( __( 'Você não tem permissão para realizar esta ação.', 'autoblogpro' ) );
            }

            delete_transient( 'autobp_extracted_data_' . get_current_user_id() ); 
            
            $generate_page_slug = 'autobp-generate-articles'; // Slug da página "Gerar Artigos"
            $redirect_url = admin_url( 'admin.php?page=' . $generate_page_slug . '&tab=url_mode' ); 
            wp_safe_redirect( add_query_arg( array( 'autobp_url_message' => 'transient_cleared' ), $redirect_url . '#url-mode-section' ) ); // redirect_url already has tab
            exit;
        }

        /**
         * Manipula a ação de reescrever e gerar um novo artigo a partir dos dados extraídos de uma URL.
         *
         * Registrado no hook `admin_post_autobp_rewrite_url_content`.
         * 1. Verifica nonce e permissões.
         * 2. Recupera os dados extraídos (texto, palavras-chave, tópicos, insights) do transient do usuário.
         * 3. Recupera a chave da API OpenAI.
         * 4. Chama o método `rewrite_and_expand_content` da classe `AutoBP_URL_Content_Processor`.
         * 5. Se bem-sucedido, cria um novo post (rascunho) com o título e conteúdo retornados.
         *    Salva a URL de origem como metadado do post.
         * 6. Limpa o transient com os dados extraídos.
         * 7. Redireciona para a Biblioteca de Artigos com mensagem de sucesso ou erro.
         * 8. Se a opção `autobp_generate_meta_description_url` (enviada via `$_POST`) estiver
         *    habilitada e a chave da API OpenAI estiver disponível, chama a API OpenAI para
         *    gerar uma meta descrição baseada no conteúdo reescrito e salva-a no metadado
         *    `_autobp_meta_description` do novo post.
         * 9. Chama o método estático `AutoBlogPro::find_internal_link_suggestions()` para
         *    analisar o conteúdo do novo post e encontrar sugestões de links internos.
         *    As sugestões retornadas são salvas no metadado `_autobp_internal_link_suggestions`.
         * 10. Gera e salva sugestões de títulos otimizados para SEO (via API OpenAI) com base
         *     no conteúdo reescrito e palavras-chave, salvando-as no metadado `_autobp_title_suggestions`.
         * 11. Calcula a densidade da palavra-chave foco e salva junto com a palavra-chave foco
         *     nos metadados `_autobp_focus_keyword` e `_autobp_keyword_density`.
         * 12. Gera sugestões de links externos relevantes (via API OpenAI) e salva no metadado
         *     `_autobp_external_link_suggestions`.
         * 13. Tenta preencher campos de plugins de SEO (Yoast/Rank Math) com a palavra-chave foco e
         *     meta descrição, se a integração estiver habilitada nas configurações.
         * - **Logging:** Logs detalhados são registrados em cada etapa principal: início da ação,
         *   validações, recuperação de dados do transient, chamada à API OpenAI para reescrita,
         *   criação do post, salvamento de metadados, geração de meta descrição (se habilitada),
         *   geração de sugestões de links internos/externos, sugestões de títulos, cálculo de
         *   densidade de palavra-chave e preenchimento de campos de SEO. Erros em qualquer
         *   etapa também são logados.
         *
         * @since 0.1.0 (Funcionalidades SEO e logging aprimorado adicionados progressivamente na versão 0.2.0)
         */
        public function handle_rewrite_url_content_action() {
            check_admin_referer( 'autobp_rewrite_url_content_action', 'autobp_rewrite_url_content_nonce' );
            $user_id = get_current_user_id();
            AutoBP_Log_Manager::info( 'Iniciando reescrita de conteúdo de URL.', array( 'user_id' => $user_id ) );

            if ( ! current_user_can( 'manage_options' ) ) { 
                AutoBP_Log_Manager::warning( 'Reescrita de conteúdo de URL falhou: Permissão negada.', array( 'user_id' => $user_id ) );
                wp_die( __( 'Você não tem permissão para realizar esta ação.', 'autoblogpro' ) );
            }

            $transient_name = 'autobp_extracted_data_' . $user_id;
            $extracted_data = get_transient( $transient_name );
            
            $redirect_url_generate_page = admin_url( 'admin.php?page=autobp-generate-articles#url-mode-section' );
            $redirect_url_library = admin_url( 'admin.php?page=autobp-article-library' );

            if ( false === $extracted_data || !is_array($extracted_data) || empty($extracted_data['text']) ) {
                AutoBP_Log_Manager::warning( 'Reescrita de conteúdo de URL: Nenhum dado extraído encontrado no transient.', array( 'user_id' => $user_id, 'transient_key' => $transient_name ) );
                wp_safe_redirect( add_query_arg( array( 'autobp_url_message' => 'no_extracted_data_to_rewrite' ), $redirect_url_generate_page ) );
                exit;
            }
            AutoBP_Log_Manager::debug( 'Dados extraídos do transient para reescrita:', array( 'user_id' => $user_id, 'transient_data_keys' => array_keys($extracted_data) ) );

            $openai_api_key = get_option( 'autobp_openai_api_key' );
            if ( empty( $openai_api_key ) ) {
                AutoBP_Log_Manager::warning( 'Reescrita de conteúdo de URL: Chave da API OpenAI não configurada.', array( 'user_id' => $user_id ) );
                wp_safe_redirect( add_query_arg( array( 'autobp_url_message' => 'rewrite_skipped_no_api_key' ), $redirect_url_generate_page ) );
                exit;
            }

            if ( ! class_exists( 'AutoBP_URL_Content_Processor' ) ) {
                require_once AUTOBP_PLUGIN_DIR . 'includes/class-autobp-url-content-processor.php';
            }
            
            // A URL original não é salva no transient $extracted_data, mas pode ser útil tê-la.
            // Por enquanto, o processador não precisa dela para reescrever, apenas os dados já processados.
            // Se precisássemos da URL original aqui, teríamos que adicioná-la ao transient $data_to_store.
            $source_url_from_transient = isset($extracted_data['source_url']) ? $extracted_data['source_url'] : 'http://dummyurl.com';
            $processor = new AutoBP_URL_Content_Processor( $source_url_from_transient );

            // Recuperar parâmetros de POST (ou usar defaults dos dados extraídos se não presentes no POST)
            // Isso permite que o usuário ajuste os parâmetros antes de reescrever.
            $target_language = isset($_POST['autobp_rewrite_language']) ? sanitize_text_field($_POST['autobp_rewrite_language']) : (isset($extracted_data['language_preference']) ? $extracted_data['language_preference'] : 'pt-BR');
            $num_h2_sections = isset($_POST['autobp_rewrite_num_h2_sections']) ? absint($_POST['autobp_rewrite_num_h2_sections']) : (isset($extracted_data['h2_preference']) ? $extracted_data['h2_preference'] : 4);
            $tone_of_voice = isset($_POST['autobp_rewrite_tone_of_voice']) ? sanitize_key($_POST['autobp_rewrite_tone_of_voice']) : 'informativo';
            $writing_style = isset($_POST['autobp_rewrite_writing_style']) ? sanitize_key($_POST['autobp_rewrite_writing_style']) : 'neutro';
            $creativity = isset($_POST['autobp_rewrite_creativity']) ? floatval($_POST['autobp_rewrite_creativity']) : 0.7;
            $article_length = isset($_POST['autobp_rewrite_article_length']) ? absint($_POST['autobp_rewrite_article_length']) : 1000;
            $generate_meta_desc_url = isset( $_POST['autobp_generate_meta_description_url'] ) ? 1 : 0;
            
            $rewrite_params_for_log = $extracted_data; 
            unset($rewrite_params_for_log['text']); 
            $rewrite_params_for_log['target_language'] = $target_language;
            $rewrite_params_for_log['num_h2_sections'] = $num_h2_sections;
            $rewrite_params_for_log['tone_of_voice'] = $tone_of_voice;
            $rewrite_params_for_log['writing_style'] = $writing_style;
            $rewrite_params_for_log['creativity'] = $creativity;
            $rewrite_params_for_log['article_length'] = $article_length;
            $rewrite_params_for_log['generate_meta_desc_url'] = $generate_meta_desc_url;

            AutoBP_Log_Manager::info( 'Iniciando chamada para rewrite_and_expand_content.', array('user_id' => $user_id, 'params' => $rewrite_params_for_log));

            $rewrite_result = $processor->rewrite_and_expand_content(
                $extracted_data['text'],
                $extracted_data['keywords'],
                $extracted_data['topics'],
                $extracted_data['additional_insights'],
                $openai_api_key,
                $target_language,
                $num_h2_sections,
                $tone_of_voice,
                $writing_style,
                $creativity,
                $article_length
            );

            if ( is_wp_error( $rewrite_result ) ) {
                // Salvar a mensagem de erro no transient para exibir na página de geração
                $extracted_data['rewrite_error_message'] = $rewrite_result->get_error_message();
                set_transient( $transient_name, $extracted_data, HOUR_IN_SECONDS );
                wp_safe_redirect( add_query_arg( array( 'autobp_url_message' => 'rewrite_failed' ), $redirect_url_generate_page ) );
                exit;
            }

            // Salvar o novo artigo como rascunho
            $new_post_args = array(
                'post_title'   => sanitize_text_field( $rewrite_result['title'] ),
                'post_content' => wp_kses_post( $rewrite_result['content'] ),
                'post_status'  => 'draft',
                'post_author'  => $user_id,
                'post_type'    => 'post',
            );
            $new_post_id = wp_insert_post( $new_post_args, true );

            if ( is_wp_error( $new_post_id ) ) {
                $extracted_data['rewrite_error_message'] = __( 'Falha ao salvar o novo artigo como rascunho: ', 'autoblogpro' ) . $new_post_id->get_error_message();
                set_transient( $transient_name, $extracted_data, HOUR_IN_SECONDS );
                wp_safe_redirect( add_query_arg( array( 'autobp_url_message' => 'rewrite_save_failed' ), $redirect_url_generate_page ) );
                exit;
            }
            
            // Salvar a URL de origem como metadado (se a URL original for passada ou estiver no $extracted_data)
            // if(isset($extracted_data['source_url'])) { // source_url não está no transient atualmente
            //    update_post_meta( $new_post_id, '_autobp_generated_from_source_url', esc_url_raw( $extracted_data['source_url'] ) );
            // }
            // Para salvar a source_url, precisaríamos recuperá-la de alguma forma, talvez do último URL submetido pelo usuário,
            // o que pode ser arriscado se ele submeteu outra URL enquanto o transient ainda existia.
            // Por agora, não salvaremos a source_url diretamente nos metas do post gerado,
            // mas o ideal seria armazenar 'source_url' no transient 'autobp_extracted_data_'.
            // Vamos assumir que $source_url da tentativa de extração original ainda está disponível no escopo se necessário,
            // ou que o usuário precisaria reinserir para regenerar (o que não é o objetivo aqui).
            // Para a regeneração, o mais importante é o texto extraído e os parâmetros de formatação.

            // Recuperar o valor do checkbox para gerar meta descrição
            $generate_meta_desc_url = isset( $_POST['autobp_generate_meta_description_url'] ) ? 1 : 0;

            update_post_meta( $new_post_id, '_autobp_generation_mode', 'url_rewrite' );
            update_post_meta( $new_post_id, '_autobp_gen_params_original_text', $extracted_data['text'] ); // Pode ser grande!
            update_post_meta( $new_post_id, '_autobp_gen_params_keywords', $extracted_data['keywords'] );
            update_post_meta( $new_post_id, '_autobp_gen_params_topics', $extracted_data['topics'] );
            update_post_meta( $new_post_id, '_autobp_gen_params_insights', $extracted_data['additional_insights'] );
            
            // Salvar os parâmetros de formatação que foram usados para a reescrita
            update_post_meta( $new_post_id, '_autobp_gen_params_language', $target_language );
            update_post_meta( $new_post_id, '_autobp_gen_params_num_h2_sections', $num_h2_sections );
            update_post_meta( $new_post_id, '_autobp_gen_params_tone_of_voice', $tone_of_voice );
            update_post_meta( $new_post_id, '_autobp_gen_params_writing_style', $writing_style );
            update_post_meta( $new_post_id, '_autobp_gen_params_creativity', $creativity );
            update_post_meta( $new_post_id, '_autobp_gen_params_article_length', $article_length );

            // Gerar meta descrição para o modo URL, se solicitado
            if ( $generate_meta_desc_url && !empty( $openai_api_key ) ) {
                $post_content_for_meta = get_post_field( 'post_content', $new_post_id );
                if ( !empty( $post_content_for_meta ) ) {
                    // Usar $target_language que já foi definido para a reescrita.
                    // Duplicação da lógica de chamada à API para meta descrição, conforme instruído.
                    $trimmed_content_for_meta = mb_substr( $post_content_for_meta, 0, 4000 );
                    // translators: %1$s: Idioma alvo (ex: "Português (Brasil)"). %2$s: Trecho do conteúdo do artigo.
                    $prompt_meta_text = sprintf(
                        __("Com base no seguinte texto de artigo, gere uma meta descrição otimizada para SEO em '%1$s'. A meta descrição deve ser concisa (idealmente entre 120-155 caracteres, máximo 160 caracteres), atraente e resumir os pontos principais do artigo. Texto do Artigo: %2$s", 'autoblogpro'),
                        $target_language, // Usando o idioma já definido para a reescrita
                        $trimmed_content_for_meta
                    );

                    $meta_api_body = array(
                        'model'    => 'gpt-3.5-turbo',
                        'messages' => array(
                            array('role' => 'system', 'content' => __('Você é um especialista em SEO que cria meta descrições excelentes, concisas e atraentes.', 'autoblogpro')),
                            array('role' => 'user', 'content' => $prompt_meta_text)
                        ),
                        'max_tokens'  => 80,
                        'temperature' => 0.4,
                    );

                    $meta_response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
                        'body'    => wp_json_encode( $meta_api_body ),
                        'headers' => array(
                            'Authorization' => 'Bearer ' . $openai_api_key,
                            'Content-Type'  => 'application/json',
                        ),
                        'timeout' => 45,
                    ) );

                    if ( !is_wp_error( $meta_response ) && wp_remote_retrieve_response_code( $meta_response ) === 200 ) {
                        $meta_response_body = json_decode( wp_remote_retrieve_body( $meta_response ), true );
                        if ( isset($meta_response_body['usage']['total_tokens']) ) {
                            self::add_to_openai_token_usage( (int) $meta_response_body['usage']['total_tokens'] );
                        }
                        if ( !empty( $meta_response_body['choices'][0]['message']['content'] ) ) {
                            $meta_description_text = sanitize_text_field( trim( $meta_response_body['choices'][0]['message']['content'] ) );
                            $meta_description_text = preg_replace( '/^"|"$/', '', $meta_description_text ); // Remover aspas
                            update_post_meta( $new_post_id, '_autobp_meta_description', $meta_description_text );
                        }
                        // Falha silenciosa se a meta descrição não puder ser gerada, o artigo principal já existe.
                    } // else: Log opcional da falha da API de meta descrição
                }
            }

            // Limpar o transient após o sucesso
            delete_transient( $transient_name );

            // Encontrar e salvar sugestões de links internos para o modo URL base
            if ( $new_post_id > 0 && !is_wp_error( $new_post_id ) ) { 
                $post_content_for_links_url = get_post_field( 'post_content', $new_post_id );
                if ( !empty( $post_content_for_links_url ) && class_exists( 'AutoBlogPro' ) && method_exists( 'AutoBlogPro', 'find_internal_link_suggestions' ) ) {
                    $link_suggestions_url = AutoBlogPro::find_internal_link_suggestions( $post_content_for_links_url, $new_post_id );
                    if ( ! empty( $link_suggestions_url ) ) {
                        update_post_meta( $new_post_id, '_autobp_internal_link_suggestions', $link_suggestions_url );
                        AutoBP_Log_Manager::info( 'Sugestões de links internos salvas para post (Modo URL).', array( 'post_id' => $new_post_id, 'suggestions_count' => count($link_suggestions_url) ) );
                    }
                }

                // Gerar sugestões de título para o modo URL, se a API Key estiver disponível
                if ( !empty( $openai_api_key ) && !empty( $post_content_for_links_url ) ) { // $post_content_for_links_url é o mesmo que $post_content_for_titles
                    $target_keywords_for_titles_arr = isset($extracted_data['keywords']) ? $extracted_data['keywords'] : array();
                    if (empty($target_keywords_for_titles_arr) && isset($rewrite_result['title'])) {
                         $target_keywords_for_titles_str = $rewrite_result['title']; // Fallback para o título gerado
                    } elseif (!empty($target_keywords_for_titles_arr)) {
                        $target_keywords_for_titles_str = implode(', ', $target_keywords_for_titles_arr);
                    } else {
                        $target_keywords_for_titles_str = ''; // Sem keywords, pode afetar a qualidade
                    }
                    
                    // $target_language já foi definido anteriormente nesta função
                    AutoBP_Log_Manager::info( "Tentando gerar sugestões de título para post {$new_post_id} (Modo URL).", array('post_id' => $new_post_id, 'keywords' => $target_keywords_for_titles_str, 'language' => $target_language));

                    $trimmed_content_for_titles = mb_substr( $post_content_for_links_url, 0, 2000 );
                    $prompt_title_text = "Com base no seguinte conteúdo de artigo e nas palavras-chave alvo, sugira 5 alternativas de títulos que sejam otimizados para SEO, atraentes e relevantes.\n";
                    $prompt_title_text .= "As palavras-chave alvo são: '{$target_keywords_for_titles_str}'.\n";
                    $prompt_title_text .= "O idioma dos títulos deve ser '{$target_language}'.\n";
                    $prompt_title_text .= "Apresente cada título sugerido em uma nova linha, sem numeração ou marcadores. Apenas os títulos.\n\n";
                    $prompt_title_text .= "Conteúdo do Artigo (resumo/início):\n{$trimmed_content_for_titles}";

                    $title_api_body = array(
                        'model'    => 'gpt-3.5-turbo',
                        'messages' => array(
                            array('role' => 'system', 'content' => __('Você é um especialista em SEO e copywriting que cria títulos de artigos de blog altamente eficazes.', 'autoblogpro')),
                            array('role' => 'user', 'content' => $prompt_title_text)
                        ),
                        'max_tokens'  => 250,
                        'temperature' => 0.7,
                    );

                    $title_response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
                        'body'    => wp_json_encode( $title_api_body ),
                        'headers' => array(
                            'Authorization' => 'Bearer ' . $openai_api_key,
                            'Content-Type'  => 'application/json',
                        ),
                        'timeout' => 60,
                    ) );

                    if ( !is_wp_error( $title_response ) && wp_remote_retrieve_response_code( $title_response ) === 200 ) {
                        $title_response_body = json_decode( wp_remote_retrieve_body( $title_response ), true );
                        if ( isset($title_response_body['usage']['total_tokens']) ) {
                            self::add_to_openai_token_usage( (int) $title_response_body['usage']['total_tokens'] );
                        }
                        if ( !empty( $title_response_body['choices'][0]['message']['content'] ) ) {
                            $raw_titles_text = trim( $title_response_body['choices'][0]['message']['content'] );
                            $title_suggestions_array = array_map( 'trim', explode( "\n", $raw_titles_text ) );
                            $title_suggestions_array = array_filter( $title_suggestions_array );

                            if ( !empty( $title_suggestions_array ) ) {
                                update_post_meta( $new_post_id, '_autobp_title_suggestions', $title_suggestions_array );
                                AutoBP_Log_Manager::info( count($title_suggestions_array) . " sugestões de título salvas para post {$new_post_id} (Modo URL).", array('post_id' => $new_post_id, 'suggestions' => $title_suggestions_array));
                            } else {
                                AutoBP_Log_Manager::warning( 'Nenhuma sugestão de título (Modo URL) foi extraída da resposta da API.', array( 'post_id' => $new_post_id, 'api_response_text' => $raw_titles_text ) );
                            }
                        }
                    } else {
                         $error_message_title_api = is_wp_error($title_response) ? $title_response->get_error_message() : 'Código de resposta HTTP: ' . wp_remote_retrieve_response_code( $title_response );
                         AutoBP_Log_Manager::error( 'Erro ao gerar sugestões de título via API (Modo URL).', array( 'post_id' => $new_post_id, 'error_details' => $error_message_title_api ) );
                    }
                }

                // Calcular e salvar densidade da palavra-chave foco para Modo URL
                $focus_keyword_url = '';
                if ( !empty($extracted_data['keywords']) && is_array($extracted_data['keywords']) && !empty($extracted_data['keywords'][0]) ) {
                    $focus_keyword_url = trim( $extracted_data['keywords'][0] );
                } elseif ( !empty($rewrite_result['title']) ) { 
                    $focus_keyword_url = trim( $rewrite_result['title'] ); 
                }

                if ( !empty($focus_keyword_url) && !empty($post_content_for_links_url) ) { // $post_content_for_links_url é o conteúdo do post
                    $density_url = self::calculate_keyword_density( $post_content_for_links_url, $focus_keyword_url );
                    if ( $density_url !== false ) {
                        update_post_meta( $new_post_id, '_autobp_focus_keyword', sanitize_text_field( $focus_keyword_url ) );
                        update_post_meta( $new_post_id, '_autobp_keyword_density', $density_url );
                        AutoBP_Log_Manager::info( 'Densidade da palavra-chave foco (Modo URL) calculada e salva.', array( 'post_id' => $new_post_id, 'focus_keyword' => $focus_keyword_url, 'density' => $density_url ) );
                    }
                } else {
                     AutoBP_Log_Manager::info( 'Não foi possível determinar palavra-chave foco ou conteúdo vazio para cálculo de densidade (Modo URL).', array( 'post_id' => $new_post_id, 'determined_keyword' => $focus_keyword_url ) );
                }

                // Gerar e salvar sugestões de links externos para Modo URL
                if ( !empty( $openai_api_key ) && !empty( $post_content_for_links_url ) ) {
                    // $keywords_string_url foi determinado para a densidade da palavra-chave, pode ser usado aqui.
                    // Se $focus_keyword_url foi usado como fallback para o título, isso é ok para links externos também.
                    $keywords_for_external_links = $focus_keyword_url; // Reutilizar a keyword determinada para densidade.
                    // $target_language foi definido no início desta função.

                    if (!empty($keywords_for_external_links)) {
                        $post_content_snippet_url = mb_substr( $post_content_for_links_url, 0, 1500 );
                        $external_link_suggestions_url = self::fetch_external_link_suggestions( $post_content_snippet_url, $keywords_for_external_links, $target_language, $openai_api_key );
                        if ( ! empty( $external_link_suggestions_url ) ) {
                           update_post_meta( $new_post_id, '_autobp_external_link_suggestions', $external_link_suggestions_url );
                           AutoBP_Log_Manager::info( 'Sugestões de links externos (Modo URL) salvas.', array( 'post_id' => $new_post_id, 'suggestions_count' => count($external_link_suggestions_url) ) );
                        }
                    } else {
                         AutoBP_Log_Manager::info( 'Palavras-chave não disponíveis para busca de links externos (Modo URL).', array( 'post_id' => $new_post_id ) );
                    }
                } else {
                    AutoBP_Log_Manager::debug( 'API Key ou conteúdo do post ausente para busca de links externos (Modo URL).', array('post_id' => $new_post_id, 'api_key_empty' => empty($openai_api_key)) );
                }

                // Preencher campos de SEO para plugins (Yoast, Rank Math) - Modo URL
                // $focus_keyword_url e $meta_description_text (se gerada) já estão disponíveis neste escopo.
                // A meta descrição para o modo URL é $meta_description_text, que foi salva em _autobp_meta_description
                $saved_focus_kw_url = get_post_meta( $new_post_id, '_autobp_focus_keyword', true ); // Já foi salvo na etapa de densidade
                $saved_meta_desc_url = get_post_meta( $new_post_id, '_autobp_meta_description', true ); // Já foi salvo acima
                
                if ($saved_focus_kw_url && $saved_meta_desc_url) {
                    self::maybe_fill_seo_plugin_fields( $new_post_id, $saved_focus_kw_url, $saved_meta_desc_url );
                } else {
                     AutoBP_Log_Manager::info( 'Palavra-chave foco ou meta descrição não disponíveis para preenchimento de plugin SEO (Modo URL).', array('post_id' => $new_post_id, 'has_kw' => !empty($saved_focus_kw_url), 'has_meta' => !empty($saved_meta_desc_url)) );
                }
            }

            wp_safe_redirect( add_query_arg( array( 'autobp_message' => 'post_created_from_url', 'post_id' => $new_post_id ), $redirect_url_library ) );
            exit;
        }
        
        /**
         * Manipula a ação de regenerar um artigo a partir da Biblioteca de Artigos.
         *
         * Recupera os parâmetros de geração originais do post meta do artigo selecionado.
         * Com base no modo de geração original ('automatic' ou 'url_rewrite'), chama o
         * respectivo gerador/processador para criar um novo artigo.
         * Salva o novo artigo como rascunho e redireciona para a Biblioteca com feedback.
         * As funcionalidades de SEO (meta descrição, links internos/externos, títulos, densidade,
         * preenchimento de plugin SEO) são aplicadas ao novo artigo gerado, similar ao fluxo
         * de geração original.
         *
         * - **Logging:** O método possui logging abrangente. Registra o início da regeneração,
         *   o ID do post original, validações de nonce e permissões, verificação da API Key,
         *   o modo de geração original, recuperação dos parâmetros de geração, chamadas às
         *   classes `AutoBP_Content_Generator` ou `AutoBP_URL_Content_Processor`,
         *   o resultado da API, a criação do novo post, o salvamento de metadados relevantes
         *   (incluindo os parâmetros de regeneração e o ID do post original), e o resultado final
         *   da operação. Erros em cada etapa são logados para facilitar a depuração.
         *
         * @since 0.1.0 (Logging e funcionalidades SEO aprimorados na versão 0.2.0)
         */
        public function handle_regenerate_post_action() {
            $original_post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
            $current_user_id = get_current_user_id(); // Corrigido para usar a variável correta
            AutoBP_Log_Manager::info( "Iniciando regeneração para o post ID: {$original_post_id}.", array( 'original_post_id' => $original_post_id, 'user_id' => $current_user_id ) );
            
            $redirect_url = admin_url( 'admin.php?page=autobp-article-library' );
            $redirect_url_with_args = $redirect_url; // Para logar a URL final com args

            if ( ! $original_post_id ) {
                AutoBP_Log_Manager::warning( 'Regeneração falhou: ID do post original inválido.', array( 'original_post_id_received' => isset( $_GET['post_id'] ) ? $_GET['post_id'] : 'N/A', 'user_id' => $current_user_id ) );
                $redirect_url_with_args = add_query_arg( array( 'autobp_message' => 'invalid_post_id', 'post_id' => 0 ), $redirect_url );
                AutoBP_Log_Manager::info( 'Redirecionando usuário após tentativa de regeneração (ID inválido).', array( 'redirect_to' => $redirect_url_with_args ) );
                wp_safe_redirect( $redirect_url_with_args );
                exit;
            }

            // Nonce é verificado por check_admin_referer que causa wp_die() em falha, então não há log customizado aqui para falha de nonce.
            check_admin_referer( 'autobp_regenerate_post_' . $original_post_id );

            if ( ! current_user_can( 'edit_posts' ) ) { // 'edit_posts' é uma capacidade geral, 'edit_post' seria para o post específico mas já foi verificado.
                AutoBP_Log_Manager::warning( "Permissão 'edit_posts' negada para regenerar post. User ID: {$current_user_id}", array( 'original_post_id' => $original_post_id, 'user_id' => $current_user_id ) );
                $redirect_url_with_args = add_query_arg( array( 'autobp_message' => 'regenerate_permission_denied', 'post_id' => $original_post_id ), $redirect_url );
                AutoBP_Log_Manager::info( 'Redirecionando usuário após tentativa de regeneração (permissão negada).', array( 'redirect_to' => $redirect_url_with_args ) );
                wp_safe_redirect( $redirect_url_with_args );
                exit;
            }

            $openai_api_key = get_option( 'autobp_openai_api_key' );
            if ( empty( $openai_api_key ) ) {
                AutoBP_Log_Manager::error( "Falha na regeneração: API Key da OpenAI não configurada.", array( 'original_post_id' => $original_post_id, 'user_id' => $current_user_id ) );
                $redirect_url_with_args = add_query_arg( array( 'autobp_message' => 'regenerate_skipped_no_api_key', 'post_id' => $original_post_id ), $redirect_url );
                AutoBP_Log_Manager::info( 'Redirecionando usuário após tentativa de regeneração (API Key ausente).', array( 'redirect_to' => $redirect_url_with_args ) );
                wp_safe_redirect( $redirect_url_with_args );
                exit;
            }

            $generation_mode = get_post_meta( $original_post_id, '_autobp_generation_mode', true );
            AutoBP_Log_Manager::info( "Modo de geração original para post ID {$original_post_id}: {$generation_mode}.", array( 'original_post_id' => $original_post_id, 'mode' => $generation_mode, 'user_id' => $current_user_id ) );
            
            $new_post_id_or_error = null; // Inicializa a variável
            $params_for_log = array(); // Para loggar os parâmetros usados na regeneração (sem dados sensíveis)

            if ( 'automatic' === $generation_mode ) {
                if ( ! class_exists( 'AutoBP_Content_Generator' ) ) {
                    require_once AUTOBP_PLUGIN_DIR . 'includes/class-autobp-content-generator.php';
                }
                $params = array(
                    'target_keywords' => get_post_meta( $original_post_id, '_autobp_gen_params_target_keywords', true ),
                    'article_length'  => (int) get_post_meta( $original_post_id, '_autobp_gen_params_article_length', true ),
                    'tone_of_voice'   => get_post_meta( $original_post_id, '_autobp_gen_params_tone_of_voice', true ),
                    'writing_style'   => get_post_meta( $original_post_id, '_autobp_gen_params_writing_style', true ),
                    'creativity'      => (float) get_post_meta( $original_post_id, '_autobp_gen_params_creativity', true ),
                    'language'        => get_post_meta( $original_post_id, '_autobp_gen_params_language', true ),
                    'num_h2_sections' => (int) get_post_meta( $original_post_id, '_autobp_gen_params_num_h2_sections', true ),
                    'niche_id'        => (int) get_post_meta( $original_post_id, '_autobp_generated_from_niche_id', true ),
                    'generate_meta_desc' => (bool) get_post_meta( $original_post_id, '_autobp_gen_params_generate_meta_desc_auto', true ), 
                );
                $params_for_log = $params; // Already contains sanitized or simple data for logging.

                if ( empty( $params['target_keywords'] ) || empty( $params['niche_id'] ) ) {
                    AutoBP_Log_Manager::error( "Parâmetros de geração essenciais ausentes para regenerar post ID {$original_post_id} (Modo: {$generation_mode}). Detalhes: Parâmetros ausentes ou inválidos.", array( 'original_post_id' => $original_post_id, 'user_id' => $current_user_id, 'retrieved_params_keys' => array_keys($params), 'actual_params_values' => $params ) );
                    $redirect_url_with_args = add_query_arg( array( 'autobp_message' => 'regenerate_missing_params', 'post_id' => $original_post_id ), $redirect_url );
                    AutoBP_Log_Manager::info( 'Redirecionando usuário após tentativa de regeneração (parâmetros ausentes).', array( 'redirect_to' => $redirect_url_with_args ) );
                    wp_safe_redirect( $redirect_url_with_args );
                    exit;
                }
                AutoBP_Log_Manager::info( "Parâmetros de geração originais recuperados para post ID {$original_post_id} (Modo: {$generation_mode})." , array( 'original_post_id' => $original_post_id, 'user_id' => $current_user_id, 'params_keys' => array_keys($params_for_log) ) );
                
                AutoBP_Log_Manager::info( "Chamando AutoBP_Content_Generator->generate() para regeneração (Modo: {$generation_mode}).", array( 'original_post_id' => $original_post_id ) );
                $generator = new AutoBP_Content_Generator( $openai_api_key, $params['niche_id'], 1, $params['target_keywords'], $params['article_length'], $params['tone_of_voice'], $params['writing_style'], $params['creativity'], '', $params['language'], $params['num_h2_sections'], $params['generate_meta_desc'] );
                $generation_result = $generator->generate(1); // Renomeado para $generation_result para clareza

                if (is_wp_error($generation_result)) {
                    AutoBP_Log_Manager::error( "Falha na API durante a regeneração ({$generation_mode}) para post ID {$original_post_id}: " . $generation_result->get_error_message(), array( 'error_code' => $generation_result->get_error_code(), 'original_post_id' => $original_post_id, 'user_id' => $current_user_id ) );
                } else {
                    AutoBP_Log_Manager::info( "Conteúdo regenerado com sucesso via API para post ID {$original_post_id} (Modo: {$generation_mode}). Novo Post ID (temporário): {$generation_result}.", array('original_post_id' => $original_post_id, 'new_post_id_candidate' => $generation_result) );
                }
                $new_post_id_or_error = $generation_result;


            } elseif ( 'url_rewrite' === $generation_mode || 'url_rewrite_regenerated' === $generation_mode ) {
                if ( ! class_exists( 'AutoBP_URL_Content_Processor' ) ) {
                    require_once AUTOBP_PLUGIN_DIR . 'includes/class-autobp-url-content-processor.php';
                }
                $params = array(
                    'original_text_snippet' => get_post_meta( $original_post_id, '_autobp_gen_params_original_text_snippet', true ),
                    'original_text_full'    => get_post_meta( $original_post_id, '_autobp_gen_params_original_text', true ), // Note: '_autobp_gen_params_original_text' might be too large if not careful
                    'keywords'              => get_post_meta( $original_post_id, '_autobp_gen_params_keywords', true ),
                    'topics'                => get_post_meta( $original_post_id, '_autobp_gen_params_topics', true ),
                    'insights'              => get_post_meta( $original_post_id, '_autobp_gen_params_insights', true ),
                    'language'              => get_post_meta( $original_post_id, '_autobp_gen_params_language', true ),
                    'num_h2_sections'       => (int) get_post_meta( $original_post_id, '_autobp_gen_params_num_h2_sections', true ),
                    'tone_of_voice'         => get_post_meta( $original_post_id, '_autobp_gen_params_tone_of_voice', true ),
                    'writing_style'         => get_post_meta( $original_post_id, '_autobp_gen_params_writing_style', true ),
                    'creativity'            => (float) get_post_meta( $original_post_id, '_autobp_gen_params_creativity', true ),
                    'article_length'        => (int) get_post_meta( $original_post_id, '_autobp_gen_params_article_length', true ),
                    'generate_meta_desc'    => (bool) get_post_meta( $original_post_id, '_autobp_gen_params_generate_meta_desc_url', true ),
                );
                $params_for_log = $params;
                unset($params_for_log['original_text_full']); // Evitar logar texto completo

                if ( empty( $params['original_text_full'] ) ) { // Texto original é essencial para o modo URL
                    AutoBP_Log_Manager::error( "Parâmetros de geração essenciais ausentes para regenerar post ID {$original_post_id} (Modo: {$generation_mode}). Detalhes: Texto original ausente.", array( 'original_post_id' => $original_post_id, 'user_id' => $current_user_id, 'retrieved_params_keys' => array_keys($params)) );
                    $redirect_url_with_args = add_query_arg( array( 'autobp_message' => 'regenerate_missing_params', 'post_id' => $original_post_id ), $redirect_url );
                    AutoBP_Log_Manager::info( 'Redirecionando usuário após tentativa de regeneração (parâmetros ausentes para modo URL).', array( 'redirect_to' => $redirect_url_with_args ) );
                    wp_safe_redirect( $redirect_url_with_args );
                    exit;
                }
                AutoBP_Log_Manager::info( "Parâmetros de geração originais recuperados para post ID {$original_post_id} (Modo: {$generation_mode}).", array( 'original_post_id' => $original_post_id, 'user_id' => $current_user_id, 'params_keys' => array_keys($params_for_log) ) );
                
                $source_url_meta = get_post_meta( $original_post_id, '_autobp_original_source_url', true ); // Pode não existir para posts mais antigos
                $processor = new AutoBP_URL_Content_Processor( $source_url_meta ? esc_url_raw($source_url_meta) : 'http://dummyurl.com/regenerated' ); 
                
                AutoBP_Log_Manager::info( "Chamando AutoBP_URL_Content_Processor->rewrite_and_expand_content() para regeneração (Modo: {$generation_mode}).", array( 'original_post_id' => $original_post_id ) );
                $rewrite_result = $processor->rewrite_and_expand_content(
                    $params['original_text_full'], $params['keywords'], $params['topics'], $params['insights'], $openai_api_key,
                    $params['language'], $params['num_h2_sections'], $params['tone_of_voice'], $params['writing_style'],
                    $params['creativity'], $params['article_length']
                );

                if ( is_wp_error( $rewrite_result ) ) {
                    AutoBP_Log_Manager::error( "Falha na API durante a regeneração ({$generation_mode}) para post ID {$original_post_id}: " . $rewrite_result->get_error_message(), array( 'error_code' => $rewrite_result->get_error_code(), 'original_post_id' => $original_post_id, 'user_id' => $current_user_id ) );
                    $new_post_id_or_error = $rewrite_result; // Passar o erro adiante
                } else {
                    AutoBP_Log_Manager::info( "Conteúdo regenerado com sucesso via API para post ID {$original_post_id} (Modo: {$generation_mode}). Título: " . mb_substr($rewrite_result['title'], 0, 70), array('original_post_id' => $original_post_id) );
                    $new_title = sanitize_text_field( $rewrite_result['title'] . __(' (Regenerado)', 'autoblogpro') );
                    $new_post_args = array(
                        'post_title'   => $new_title,
                        'post_content' => wp_kses_post( $rewrite_result['content'] ),
                        'post_status'  => 'draft', 'post_author'  => $user_id, 'post_type'    => 'post',
                    );
                    $new_post_id = wp_insert_post( $new_post_args, true );

                    if (is_wp_error($new_post_id) || $new_post_id === 0) {
                        $error_msg = is_wp_error($new_post_id) ? $new_post_id->get_error_message() : 'ID do post retornado foi 0';
                        AutoBP_Log_Manager::error( "Falha ao salvar rascunho regenerado ({$generation_mode}) para post ID {$original_post_id}: {$error_msg}", array( 'original_post_id' => $original_post_id, 'user_id' => $current_user_id ) );
                        $new_post_id_or_error = is_wp_error($new_post_id) ? $new_post_id : new WP_Error('insert_failed', $error_msg);
                    } else {
                        AutoBP_Log_Manager::info( "Novo rascunho regenerado criado (ID: {$new_post_id}, Título: '{$new_title}') a partir do post original ID {$original_post_id}.", array( 'new_post_id' => $new_post_id, 'original_post_id' => $original_post_id, 'user_id' => $current_user_id ) );
                        update_post_meta( $new_post_id, '_autobp_generation_mode', 'url_rewrite_regenerated' );
                        update_post_meta( $new_post_id, '_autobp_regenerated_from_post_id', $original_post_id );
                        if($source_url_meta) update_post_meta( $new_post_id, '_autobp_original_source_url', esc_url_raw($source_url_meta) );
                        
                         $original_niche_id = get_post_meta( $original_post_id, '_autobp_generated_from_niche_id', true );
                         if($original_niche_id){ update_post_meta( $new_post_id, '_autobp_generated_from_niche_id', $original_niche_id ); }
                        AutoBP_Log_Manager::info( "Metadados relevantes salvos para novo post regenerado ID {$new_post_id}." );
                        
                        // Aqui você chamaria as funções de meta desc, títulos, links, keyword density, SEO plugin fill, etc.
                        // Essas funções já contêm seus próprios logs.
                        // Por exemplo:
                        // $post_content_for_seo = get_post_field('post_content', $new_post_id);
                        // if ($params['generate_meta_desc']) { ... }
                        // ... e assim por diante para outras features de SEO ...
                        $new_post_id_or_error = $new_post_id;
                    }
                }
            } else {
                AutoBP_Log_Manager::error( "Modo de geração desconhecido ('{$generation_mode}') para post ID {$original_post_id}. Não é possível regenerar.", array( 'original_post_id' => $original_post_id, 'user_id' => $current_user_id, 'retrieved_mode' => $generation_mode ) );
                $redirect_url_with_args = add_query_arg( array( 'autobp_message' => 'regenerate_unknown_mode', 'post_id' => $original_post_id ), $redirect_url );
                AutoBP_Log_Manager::info( 'Redirecionando usuário após tentativa de regeneração (modo desconhecido).', array( 'redirect_to' => $redirect_url_with_args ) );
                wp_safe_redirect( $redirect_url_with_args );
                exit;
            }

            if ( is_wp_error( $new_post_id_or_error ) ) {
                $error_string = $new_post_id_or_error->get_error_message();
                AutoBP_Log_Manager::error( "Falha ao regenerar post ID {$original_post_id} (Modo: {$generation_mode}): " . $error_string, array( 'original_post_id' => $original_post_id, 'user_id' => $current_user_id, 'error_code' => $new_post_id_or_error->get_error_code() ) );
                $redirect_url_with_args = add_query_arg( array( 'autobp_message' => 'regenerate_failed', 'post_id' => $original_post_id, 'error' => urlencode($error_string) ), $redirect_url );
            } elseif ( $new_post_id_or_error > 0 ) {
                // Sucesso - os logs de criação e metadados já foram feitos dentro dos blocos if/elseif acima.
                // Aqui apenas logamos o resultado final da operação de regeneração.
                AutoBP_Log_Manager::info( "Post ID {$original_post_id} regenerado com sucesso como novo post ID {$new_post_id_or_error} (Modo: {$generation_mode}).", array( 'original_post_id' => $original_post_id, 'new_post_id' => $new_post_id_or_error, 'user_id' => $current_user_id ) );
                $redirect_url_with_args = add_query_arg( array( 'autobp_message' => 'regenerate_success', 'post_id' => $original_post_id, 'new_post_id' => $new_post_id_or_error ), $redirect_url );
            } else {
                // Caso inesperado onde não é WP_Error nem um ID > 0
                AutoBP_Log_Manager::error( "Falha desconhecida ao regenerar post ID {$original_post_id} (Modo: {$generation_mode}). Resultado não foi WP_Error nem um ID de post válido.", array( 'original_post_id' => $original_post_id, 'user_id' => $current_user_id, 'result_from_generator' => $new_post_id_or_error ) );
                $redirect_url_with_args = add_query_arg( array( 'autobp_message' => 'regenerate_failed_unknown', 'post_id' => $original_post_id ), $redirect_url );
            }
            AutoBP_Log_Manager::info( 'Redirecionando usuário após regeneração de post.', array( 'redirect_to' => $redirect_url_with_args ) );
            wp_safe_redirect( $redirect_url_with_args );
            }
            exit;
        }

        /**
         * Encontra sugestões de links internos com base no conteúdo de um post.
         *
         * Este método estático analisa o conteúdo textual de um post (`$post_content`)
         * em busca de ocorrências dos títulos de outros posts publicados no site.
         *
         * Lógica:
         * 1. Valida os parâmetros de entrada (`$post_content`, `$current_post_id`).
         * 2. Busca até 200 posts publicados (excluindo o `$current_post_id`) ordenados por data.
         *    Apenas os IDs são buscados inicialmente para performance.
         * 3. Remove tags HTML do `$post_content` e o converte para minúsculas para
         *    comparação insensível a maiúsculas/minúsculas.
         * 4. Itera sobre os IDs dos posts publicados:
         *    a. Obtém o título do post alvo.
         *    b. Converte o título alvo para minúsculas.
         *    c. Verifica se o título tem um comprimento mínimo (ex: 4 caracteres) para ser
         *       considerado uma âncora válida e evitar falsos positivos com palavras curtas.
         *    d. Usa `strpos()` para verificar se o título alvo (em minúsculas) existe
         *       dentro do conteúdo do post original (em minúsculas).
         *    e. Se uma correspondência for encontrada e o permalink do post alvo ainda não
         *       foi adicionado às sugestões, adiciona um array à lista de `$suggestions`.
         *       Este array contém:
         *       - `anchor_text`: O título original do post alvo (para usar como texto âncora).
         *       - `url`: O permalink do post alvo.
         *       - `post_id`: O ID do post alvo.
         * 5. Limita o número de sugestões a um máximo de 5 para evitar sobrecarregar o usuário.
         *
         * @since 0.2.0
         * @access public
         * @static
         * @param string $post_content O conteúdo do post a ser analisado para sugestões de links.
         * @param int    $current_post_id O ID do post que está sendo analisado (para evitar auto-links).
         * @return array Um array de sugestões de links. Cada sugestão é um array associativo com
         *               as chaves 'anchor_text', 'url', e 'post_id'. Retorna um array vazio
         *               se nenhuma sugestão for encontrada ou em caso de parâmetros inválidos.
         */
        public static function find_internal_link_suggestions( $post_content, $current_post_id ) {
            $suggestions = array();
            if ( empty( $post_content ) || !is_numeric($current_post_id) || $current_post_id <= 0 ) {
                return $suggestions;
            }

            $args = array(
                'post_type'      => 'post', // Considerar outros CPTs públicos se relevante
                'post_status'    => 'publish',
                'posts_per_page' => 200, // Limitar para performance, -1 pode ser muito em sites grandes
                'fields'         => 'ids', 
                'post__not_in'   => array( $current_post_id ), 
                'orderby'        => 'date', // Tentar priorizar posts mais recentes ou relevantes
                'order'          => 'DESC',
            );
            $published_posts_ids = get_posts( $args );

            if ( empty( $published_posts_ids ) ) {
                return $suggestions;
            }

            // Remover tags HTML e converter para minúsculas para comparação
            // Usar mb_strtolower para suporte a caracteres multibyte
            $post_content_stripped = wp_strip_all_tags( $post_content );
            if (function_exists('mb_strtolower')) {
                $post_content_lower = mb_strtolower( $post_content_stripped );
            } else {
                $post_content_lower = strtolower( $post_content_stripped );
            }


            foreach ( $published_posts_ids as $target_post_id ) {
                $target_title = get_the_title( $target_post_id );
                
                if ( empty( $target_title ) ) {
                    continue;
                }

                if (function_exists('mb_strtolower')) {
                    $target_title_lower = mb_strtolower( $target_title );
                } else {
                    $target_title_lower = strtolower( $target_title );
                }

                // Verificar se o título tem um tamanho mínimo para ser considerado uma boa âncora
                // Títulos muito curtos (ex: "e", "ou", "um") podem gerar muitos falsos positivos
                if ( mb_strlen( $target_title_lower ) < 4 ) { // Ajustar conforme necessário
                    continue;
                }
                
                // Usar strpos para encontrar a ocorrência do título no conteúdo.
                // Para maior precisão, idealmente usaríamos regex para encontrar palavras inteiras (\b),
                // mas strpos é mais performático para uma primeira abordagem.
                if ( strpos( $post_content_lower, $target_title_lower ) !== false ) {
                    
                    $target_permalink = get_permalink( $target_post_id );
                    if ( ! $target_permalink ) continue; // Pular se não conseguir o permalink

                    $url_already_suggested = false;
                    foreach ($suggestions as $s) {
                        if ($s['url'] === $target_permalink) {
                            $url_already_suggested = true;
                            break;
                        }
                    }

                    if (!$url_already_suggested) {
                        $suggestions[] = array(
                            'anchor_text' => $target_title, 
                            'url'         => $target_permalink,
                            'post_id'     => $target_post_id
                        );
                    }
                }
                
                if (count($suggestions) >= 5) { 
                    break; 
                }
            }
            return $suggestions;
        }

        /**
         * Manipula a ação de limpar todos os logs do sistema.
         *
         * Esta função é chamada via `admin_post_autobp_clear_all_logs` quando o usuário
         * clica no botão "Limpar Todos os Logs" na página "Logs do Sistema".
         *
         * Lógica:
         * 1. Verifica o nonce `autobp_clear_all_logs_nonce` para segurança.
         * 2. Verifica se o usuário atual possui a capacidade `manage_options`.
         *    Se não, encerra a execução com `wp_die()`.
         * 3. Usa a variável global `$wpdb` para executar uma query `TRUNCATE TABLE`
         *    na tabela de logs do plugin (`{$wpdb->prefix}autoblogpro_logs`).
         *    A query `TRUNCATE` remove todas as linhas da tabela de forma eficiente.
         * 4. Registra um evento no próprio sistema de logs (nível WARNING) para
         *    documentar que a ação de limpeza foi realizada e por qual usuário.
         *    Isso é feito antes do redirecionamento.
         * 5. Redireciona o usuário de volta para a página "Logs do Sistema"
         *    (`admin.php?page=autobp-system-logs`) adicionando um parâmetro de URL
         *    `autobp_log_message=cleared` para que uma mensagem de sucesso possa ser
         *    exibida na página de logs.
         *
         * @since 0.2.0
         * @global wpdb $wpdb Objeto de acesso ao banco de dados do WordPress.
         * @access public
         */
        public function handle_clear_all_logs_action() {
            check_admin_referer( 'autobp_clear_all_logs_action', 'autobp_clear_all_logs_nonce' );

            if ( ! current_user_can( 'manage_options' ) ) {
                AutoBP_Log_Manager::warning( 'Tentativa de limpar logs falhou: Permissão negada.', array( 'user_id' => get_current_user_id() ) );
                wp_die( __( 'Você não tem permissão para limpar os logs.', 'autoblogpro' ) );
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'autoblogpro_logs';
            $result = $wpdb->query( "TRUNCATE TABLE {$table_name}" ); // TRUNCATE é mais eficiente que DELETE FROM para limpar toda a tabela.

            if ( false === $result ) {
                AutoBP_Log_Manager::error( 'Falha ao truncar a tabela de logs.', array( 'table_name' => $table_name, 'db_error' => $wpdb->last_error ) );
                // Considerar adicionar uma mensagem de erro no redirecionamento se a limpeza falhar.
            } else {
                // Loga o evento DEPOIS de confirmar que a ação foi (provavelmente) bem-sucedida.
                AutoBP_Log_Manager::warning( 'Todos os logs do sistema foram limpos.', array( 'user_id' => get_current_user_id(), 'table_name' => $table_name ) );
            }
            
            $redirect_url = admin_url( 'admin.php?page=autobp-system-logs&autobp_log_message=cleared' );
            wp_safe_redirect( $redirect_url );
            exit;
        }


        /**
         * Manipula a submissão do formulário do "Modo Automático" para gerar artigos.
         *
         * Esta função é o handler para a action `admin_post_autobp_generate_articles_auto`,
         * acionada quando o formulário da aba "Modo Automático" na página "Gerar Artigos"
         * é submetido.
         *
         * Lógica:
         * 1. Verifica o nonce `autobp_generate_articles_auto_nonce` para segurança.
         * 2. Coleta e sanitiza todos os parâmetros enviados via `$_POST`. Isso inclui:
         *    - `autobp_niche_id`: ID do nicho selecionado.
         *    - `autobp_num_articles`: Número de artigos a gerar.
         *    - `autobp_target_keywords`: Palavras-chave alvo (string, separada por vírgulas).
         *    - Parâmetros de personalização da IA: `autobp_article_length`, `autobp_tone_of_voice`,
         *      `autobp_writing_style`, `autobp_creativity`, `autobp_negative_keywords`,
         *      `autobp_language`, `autobp_num_h2_sections`.
         *    - `autobp_generate_meta_description_auto`: Checkbox para gerar meta descrição (1 se marcado).
         * 3. Valida se os parâmetros essenciais (ID do nicho, palavras-chave, número de artigos)
         *    foram fornecidos. Se não, redireciona com uma mensagem de erro.
         * 4. Converte a string de palavras-chave em um array.
         * 5. Recupera a chave da API OpenAI das opções do WordPress.
         * 6. Inclui e instancia a classe `AutoBP_Content_Generator` com todos os parâmetros coletados.
         * 7. Itera `$num_articles_to_generate` vezes, chamando o método `generate()` do objeto
         *    `AutoBP_Content_Generator` para cada artigo. O método `generate()` já lida internamente
         *    com a geração de meta descrição e sugestões de links internos se as opções estiverem ativas.
         * 8. Coleta os resultados de cada chamada a `generate()` (que pode ser um ID de post em caso
         *    de sucesso, ou um objeto `WP_Error` em caso de falha) em um array `$results_data`.
         *    Este array armazena o status, ID do post, título, link de edição ou mensagem de erro.
         * 9. Salva o array `$results_data` em um transient do WordPress (nomeado
         *    `autobp_generation_results_{user_id}`) para que possa ser exibido na página
         *    após o redirecionamento.
         * 10. Redireciona o usuário de volta para a página "Gerar Artigos", especificamente para
         *     a aba "Modo Automático" (`&tab=automatic_mode`), e adiciona um parâmetro de URL
         *     `autobp_message=generation_attempted`. A página "Gerar Artigos" então lê
         *     o transient para exibir os resultados detalhados da tentativa de geração.
         *
         * @since 0.2.0
         * @access public
         */
        public function handle_generate_articles_auto_action() {
            check_admin_referer( 'autobp_generate_articles_auto_action', 'autobp_generate_articles_auto_nonce' );
            $user_id = get_current_user_id();
            AutoBP_Log_Manager::info( 'Recebida submissão do formulário Modo Automático.', array( 'user_id' => $user_id, 'post_data' => $_POST ) );

            $redirect_url = admin_url( 'admin.php?page=autobp-generate-articles&tab=automatic_mode' );

            // Coleta e sanitização dos dados do formulário.
            $niche_id = isset( $_POST['autobp_niche_id'] ) ? intval( $_POST['autobp_niche_id'] ) : 0;
            $num_articles_to_generate = isset( $_POST['autobp_num_articles'] ) ? intval( $_POST['autobp_num_articles'] ) : 1;
            if ($num_articles_to_generate < 1) $num_articles_to_generate = 1;
            
            $target_keywords_str = isset( $_POST['autobp_target_keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['autobp_target_keywords'] ) ) : '';
            $article_length = isset( $_POST['autobp_article_length'] ) ? intval( $_POST['autobp_article_length'] ) : 500;
            if ($article_length < 50) $article_length = 50;
            
            $tone_of_voice = isset( $_POST['autobp_tone_of_voice'] ) ? sanitize_key( $_POST['autobp_tone_of_voice'] ) : 'neutro';
            $writing_style = isset( $_POST['autobp_writing_style'] ) ? sanitize_key( $_POST['autobp_writing_style'] ) : 'informativo';
            $creativity = isset( $_POST['autobp_creativity'] ) ? floatval( $_POST['autobp_creativity'] ) : 0.7;
            if ($creativity < 0.1) $creativity = 0.1; if ($creativity > 1.0) $creativity = 1.0;
            
            $negative_keywords = isset( $_POST['autobp_negative_keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['autobp_negative_keywords'] ) ) : '';
            $language = isset( $_POST['autobp_language'] ) ? sanitize_text_field( wp_unslash( $_POST['autobp_language'] ) ) : 'pt-BR';
            $num_h2_sections = isset( $_POST['autobp_num_h2_sections'] ) ? intval( $_POST['autobp_num_h2_sections'] ) : 4;
            if ( $num_h2_sections < 1 ) $num_h2_sections = 1; if ( $num_h2_sections > 10 ) $num_h2_sections = 10;
            $generate_meta_desc_auto = isset( $_POST['autobp_generate_meta_description_auto'] ) ? 1 : 0;

            if ( ! $niche_id || empty($target_keywords_str) || $num_articles_to_generate <= 0 ) {
                AutoBP_Log_Manager::warning( 'Tentativa de geração (Modo Automático) com parâmetros ausentes.', array( 'niche_id' => $niche_id, 'target_keywords_str' => $target_keywords_str, 'num_articles' => $num_articles_to_generate, 'user_id' => $user_id ) );
                $redirect_url = add_query_arg( array( 'autobp_message' => 'params_missing' ), $redirect_url );
                wp_safe_redirect( $redirect_url );
                exit;
            }
            
            $target_keywords = array_map('trim', explode(',', $target_keywords_str));
            $api_key = get_option( 'autobp_openai_api_key', '' ); // Corrigido para usar o nome correto da opção

            if ( ! class_exists( 'AutoBP_Content_Generator' ) ) {
                if ( file_exists( AUTOBP_PLUGIN_DIR . 'includes/class-autobp-content-generator.php' ) ) {
                    require_once AUTOBP_PLUGIN_DIR . 'includes/class-autobp-content-generator.php';
                } else {
                    AutoBP_Log_Manager::error( 'Arquivo do gerador de conteúdo não encontrado ao tentar gerar artigos (Modo Automático).', array('path_checked' => AUTOBP_PLUGIN_DIR . 'includes/class-autobp-content-generator.php') );
                    // Adicionar uma mensagem de erro para o usuário aqui seria bom, mas o sistema de feedback atual é via query args.
                    // Esta é uma falha crítica, então talvez um wp_die ou uma mensagem de erro mais proeminente.
                    // Por enquanto, apenas redireciona com uma mensagem genérica se possível.
                    $redirect_url = add_query_arg( array( 'autobp_message' => 'generator_missing' ), $redirect_url ); // Mensagem genérica
                    wp_safe_redirect($redirect_url);
                    exit;
                }
            }
            
            $generator = new AutoBP_Content_Generator( 
                $api_key, $niche_id, $num_articles_to_generate, $target_keywords, $article_length,
                $tone_of_voice, $writing_style, $creativity, $negative_keywords, $language,
                $num_h2_sections, $generate_meta_desc_auto
            );

            $results_data = array(); // Para armazenar ID do post ou mensagem de erro
            for ( $i = 0; $i < $num_articles_to_generate; $i++ ) {
                $result = $generator->generate( $i + 1 ); 
                if (is_wp_error($result)) {
                    $results_data[] = array('status' => 'error', 'message' => $result->get_error_message(), 'code' => $result->get_error_code());
                } else {
                    $results_data[] = array('status' => 'success', 'post_id' => $result, 'title' => get_the_title($result), 'edit_link' => get_edit_post_link($result));
                }
            }
            
            // Armazenar resultados no transient para serem exibidos na página de admin
            set_transient('autobp_generation_results_' . $user_id, $results_data, MINUTE_IN_SECONDS * 5);

            $redirect_url = add_query_arg( array( 'autobp_message' => 'generation_attempted' ), $redirect_url );
            wp_safe_redirect( $redirect_url );
            exit;
        }

        /**
         * Manipula a requisição AJAX para buscar imagens da Pexels.
         *
         * Action: `wp_ajax_autobp_fetch_pexels_images`
         * Verifica nonce, post_id e permissões.
         * Obtém a API Key da Pexels, instancia `AutoBP_Pexels_Image_Finder` e
         * chama `search_images()` usando o título do post como query inicial.
         * Retorna uma resposta JSON com as imagens encontradas ou um erro.
         *
         * @since 0.2.0
         */
        public function handle_ajax_fetch_pexels_images() {
            check_ajax_referer( 'autobp_fetch_pexels_nonce', '_ajax_nonce' );

            $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
            $query_text = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
            $user_id = get_current_user_id();
            AutoBP_Log_Manager::info( 'Requisição AJAX para buscar imagens Pexels.', array( 'post_id' => $post_id, 'query' => $query_text, 'user_id' => $user_id ) );

            if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
                AutoBP_Log_Manager::error( 'Busca Pexels falhou: ID do post inválido ou permissão negada.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                wp_send_json_error( array( 'message' => __( 'ID do post inválido ou permissão negada.', 'autoblogpro' ) ) );
            }

            if ( empty( $query_text ) ) {
                AutoBP_Log_Manager::warning( 'Busca Pexels: Query de busca vazia.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                wp_send_json_error( array( 'message' => __( 'Termo de busca não pode ser vazio.', 'autoblogpro' ) ) );
            }
            
            $pexels_api_key = get_option( 'autobp_pexels_api_key' );
            if ( empty( $pexels_api_key ) ) {
                AutoBP_Log_Manager::error( 'Busca Pexels falhou: Chave da API Pexels não configurada.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                wp_send_json_error( array( 'message' => __( 'Chave da API Pexels não configurada nas Configurações do AutoBlogPro.', 'autoblogpro' ) ) );
            }

            if ( ! class_exists( 'AutoBP_Pexels_Image_Finder' ) ) {
                $finder_class_path = AUTOBP_PLUGIN_DIR . 'includes/class-autobp-pexels-image-finder.php';
                if ( file_exists( $finder_class_path ) ) {
                    require_once $finder_class_path;
                } else {
                    AutoBP_Log_Manager::error( 'Busca Pexels falhou: Classe AutoBP_Pexels_Image_Finder não encontrada.', array( 'path_checked' => $finder_class_path ) );
                    wp_send_json_error( array( 'message' => __( 'Erro interno: Classe do buscador de imagens não encontrada.', 'autoblogpro' ) ) );
                }
            }

            $finder = new AutoBP_Pexels_Image_Finder( $pexels_api_key );
            $images_result = $finder->search_images( $query_text, 10 ); // Buscar 10 imagens por padrão

            if ( is_wp_error( $images_result ) ) {
                AutoBP_Log_Manager::error( 'Erro ao buscar imagens na Pexels: ' . $images_result->get_error_message(), array( 'post_id' => $post_id, 'query' => $query_text, 'error_code' => $images_result->get_error_code() ) );
                wp_send_json_error( array( 'message' => $images_result->get_error_message() ) );
            } else {
                AutoBP_Log_Manager::info( 'Busca de imagens Pexels bem-sucedida.', array( 'post_id' => $post_id, 'query' => $query_text, 'images_found' => count($images_result) ) );
                wp_send_json_success( array( 'images' => $images_result ) );
            }
            // wp_die() é chamado por wp_send_json_success/error
        }

        /**
         * Manipula a requisição AJAX para definir uma imagem da Pexels como imagem destacada.
         *
         * Action: `wp_ajax_autobp_set_pexels_featured_image`
         * - Verifica nonce e permissões.
         * - Baixa a imagem da URL fornecida usando `media_sideload_image()`.
         * - Anexa a imagem baixada ao post especificado.
         * - Define a imagem anexada como a imagem destacada do post (`set_post_thumbnail()`).
         * - **Otimização de Alt Text (desde 0.2.0):**
         *   - Após o sideload e definição da imagem destacada, o método tenta gerar um texto alternativo (alt text) otimizado para SEO.
         *   - Se a chave da API OpenAI estiver configurada nas opções do plugin:
         *     a. Constrói um prompt para a API OpenAI (gpt-3.5-turbo) que inclui:
         *        - O texto alternativo original da imagem Pexels (fornecido no request AJAX).
         *        - O título do post ao qual a imagem está sendo anexada.
         *        - A palavra-chave foco do post (se disponível no metadado `_autobp_focus_keyword`).
         *        - Instruções para gerar um alt text conciso (máx. 125 caracteres), descritivo, que incorpore o tema do artigo e palavras-chave, e descreva a imagem.
         *     b. Chama a API OpenAI.
         *     c. Se a API retornar um alt text válido, ele é usado.
         *   - Se a API OpenAI não estiver configurada, ou se a chamada falhar, ou se a API retornar um texto vazio,
         *     o método utiliza o texto alternativo original da Pexels (passado via `$_POST['image_alt']`) como fallback.
         *   - O texto alternativo final (otimizado ou original) é então salvo no metadado `_wp_attachment_image_alt` do novo anexo da imagem.
         * - Retorna uma resposta JSON indicando sucesso ou falha.
         * - Logs detalhados são registrados durante todo o processo, incluindo a tentativa de geração de alt text e o resultado.
         *
         * @since 0.2.0
         */
        public function handle_ajax_set_pexels_featured_image() {
            check_ajax_referer( 'autobp_set_pexels_featured_image_nonce', '_ajax_nonce' );

            $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
            $image_url = isset( $_POST['image_url'] ) ? esc_url_raw( $_POST['image_url'] ) : '';
            $image_alt = isset( $_POST['image_alt'] ) ? sanitize_text_field( wp_unslash( $_POST['image_alt'] ) ) : '';
            $image_description = isset( $_POST['image_description'] ) ? sanitize_text_field( wp_unslash( $_POST['image_description'] ) ) : '';
            // $image_pexels_id = isset( $_POST['image_pexels_id'] ) ? sanitize_text_field( $_POST['image_pexels_id'] ) : ''; // Para referência futura, não usado diretamente no sideload.

            $user_id = get_current_user_id();
            AutoBP_Log_Manager::info( 'Requisição AJAX para definir imagem destacada Pexels.', array( 'post_id' => $post_id, 'image_url' => $image_url, 'user_id' => $user_id ) );

            if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'upload_files' ) ) {
                AutoBP_Log_Manager::error( 'Definir imagem destacada Pexels falhou: ID do post inválido ou permissão negada.', array( 'post_id' => $post_id, 'user_id' => $user_id, 'image_url' => $image_url ) );
                wp_send_json_error( array( 'message' => __( 'ID do post inválido ou permissão negada para editar o post ou fazer upload de arquivos.', 'autoblogpro' ) ) );
            }

            if ( empty( $image_url ) || ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
                AutoBP_Log_Manager::error( 'Definir imagem destacada Pexels falhou: URL da imagem inválida.', array( 'post_id' => $post_id, 'image_url' => $image_url ) );
                wp_send_json_error( array( 'message' => __( 'URL da imagem inválida.', 'autoblogpro' ) ) );
            }

            // Incluir arquivos necessários para sideloading
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/image.php' );

            // Tenta obter um nome de arquivo da URL para o título da imagem
            // Remove query strings da URL para basename
            $file_array = array();
            preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)\b/i', $image_url, $matches);
            if ($matches) {
                $file_array['name'] = basename($matches[0]);
            } else {
                // Fallback para um nome genérico se a regex falhar ou não for uma extensão comum
                $file_array['name'] = 'pexels_image_' . $post_id . '_' . time() . '.jpg';
            }
            
            // Para media_sideload_image, o título/descrição é passado como segundo argumento.
            // O terceiro argumento é 'id' para retornar o ID do anexo.
            $attachment_id = media_sideload_image( $image_url, $post_id, $image_description, 'id' );

            if ( is_wp_error( $attachment_id ) ) {
                AutoBP_Log_Manager::error( 'Erro ao baixar/anexar imagem Pexels (media_sideload_image): ' . $attachment_id->get_error_message(), array( 'post_id' => $post_id, 'image_url' => $image_url, 'error_code' => $attachment_id->get_error_code() ) );
                wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
            }

            // Definir como imagem destacada
            $set_thumb_result = set_post_thumbnail( $post_id, $attachment_id );

            if ( false === $set_thumb_result ) {
                 AutoBP_Log_Manager::error( 'Falha ao definir imagem como destacada (set_post_thumbnail).', array( 'post_id' => $post_id, 'attachment_id' => $attachment_id ) );
                 // Nota: media_sideload_image já pode ter anexado a imagem, mas set_post_thumbnail falhou.
                 // O usuário pode precisar definir manualmente ou tentar novamente.
                 wp_send_json_error( array( 'message' => __( 'Imagem baixada, mas falha ao definir como destacada.', 'autoblogpro' ) ) );
            }

            // Atualizar metadados do anexo (Alt Text)
            // $image_alt é o original da Pexels, vindo do POST
            $final_alt_text = $image_alt; // Default to Pexels alt text

            $openai_api_key = get_option( 'autobp_openai_api_key' );
            $post_title = get_the_title( $post_id );
            $focus_keyword = get_post_meta( $post_id, '_autobp_focus_keyword', true );

            if ( !empty($openai_api_key) ) {
                AutoBP_Log_Manager::info( 'Tentando gerar Alt Text otimizado via OpenAI.', array('post_id' => $post_id, 'attachment_id' => $attachment_id) );
                $prompt_parts = array(
                    "Preciso de um texto alternativo (alt text) otimizado para SEO para uma imagem.",
                    "A imagem original tem a seguinte descrição/alt text: \"{$image_alt}\".",
                    "Esta imagem será usada como imagem destacada para um artigo de blog intitulado: \"{$post_title}\"."
                );
                if (!empty($focus_keyword)) {
                    $prompt_parts[] = "A palavra-chave foco do artigo é: \"{$focus_keyword}\".";
                }
                $prompt_parts[] = "Gere um alt text conciso (máximo 125 caracteres), altamente descritivo, que incorpore o tema do artigo e as palavras-chave relevantes (se fornecidas), ao mesmo tempo que descreve a imagem.";
                $prompt_parts[] = "Responda APENAS com o texto do alt text gerado.";
                $alt_text_prompt = implode("\n", $prompt_parts);

                $alt_text_api_body = array(
                    'model'    => 'gpt-3.5-turbo',
                    'messages' => array(
                        array('role' => 'system', 'content' => 'Você é um especialista em SEO que cria textos alternativos (alt text) para imagens de forma concisa e descritiva.'),
                        array('role' => 'user', 'content' => $alt_text_prompt)
                    ),
                    'max_tokens'  => 70, 
                    'temperature' => 0.4,
                );

                $alt_text_response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
                    'body'    => json_encode( $alt_text_api_body ),
                    'headers' => array( 'Authorization' => 'Bearer ' . $openai_api_key, 'Content-Type'  => 'application/json' ),
                    'timeout' => 45,
                ) );

                if ( !is_wp_error( $alt_text_response ) && wp_remote_retrieve_response_code( $alt_text_response ) === 200 ) {
                    $alt_text_response_body = json_decode( wp_remote_retrieve_body( $alt_text_response ), true );
                    if ( isset($alt_text_response_body['usage']['total_tokens']) ) {
                        self::add_to_openai_token_usage( (int) $alt_text_response_body['usage']['total_tokens'] );
                    }
                    if ( !empty( $alt_text_response_body['choices'][0]['message']['content'] ) ) {
                        $generated_alt_text = sanitize_text_field( trim( $alt_text_response_body['choices'][0]['message']['content'] ) );
                        // Remover aspas do início e fim, se a IA adicionar
                        $generated_alt_text = preg_replace('/^"|"$/', '', $generated_alt_text);
                        if (!empty($generated_alt_text)) {
                            $final_alt_text = $generated_alt_text;
                            AutoBP_Log_Manager::info( 'Alt text otimizado gerado via OpenAI.', array('post_id' => $post_id, 'attachment_id' => $attachment_id, 'generated_alt' => $final_alt_text) );
                        } else {
                             AutoBP_Log_Manager::warning( 'OpenAI retornou um alt text vazio, usando original da Pexels.', array('post_id' => $post_id, 'attachment_id' => $attachment_id, 'pexels_alt' => $image_alt) );
                        }
                    } else {
                         AutoBP_Log_Manager::warning( 'Falha ao extrair alt text da resposta da OpenAI, usando original da Pexels.', array('post_id' => $post_id, 'attachment_id' => $attachment_id, 'pexels_alt' => $image_alt, 'response_body' => $alt_text_response_body) );
                    }
                } else {
                    $error_message_alt_api = is_wp_error($alt_text_response) ? $alt_text_response->get_error_message() : 'Código HTTP: ' . wp_remote_retrieve_response_code( $alt_text_response );
                    AutoBP_Log_Manager::warning( 'Falha na chamada da API OpenAI para gerar alt text, usando original da Pexels.', array('post_id' => $post_id, 'attachment_id' => $attachment_id, 'pexels_alt' => $image_alt, 'error_details' => $error_message_alt_api) );
                }
            } else {
                 AutoBP_Log_Manager::info( 'API Key da OpenAI não configurada, usando alt text original da Pexels.', array('post_id' => $post_id, 'attachment_id' => $attachment_id) );
            }

            if ( ! empty( $final_alt_text ) ) {
                update_post_meta( $attachment_id, '_wp_attachment_image_alt', $final_alt_text );
                AutoBP_Log_Manager::info( "Alt text final definido para anexo {$attachment_id} do post {$post_id}: " . $final_alt_text );
            }
            
            // Atualizar título do anexo se necessário (media_sideload_image usa a descrição, mas podemos querer algo mais específico)
            // $attachment_data = array(
            // 'ID' => $attachment_id,
            // 'post_title' => sanitize_file_name( strtok( basename( $image_url ), '?' ) ), // Ou um título mais descritivo
            // );
            // wp_update_post( $attachment_data );


            AutoBP_Log_Manager::info( 'Imagem Pexels definida como destacada com sucesso (incluindo processamento de alt text).', array( 'post_id' => $post_id, 'attachment_id' => $attachment_id, 'image_url' => $image_url ) );
            wp_send_json_success( array( 
                'message' => __( 'Imagem destacada definida com sucesso.', 'autoblogpro' ), 
                'attachment_id' => $attachment_id,
                'final_alt_text_used' => $final_alt_text // Opcional: retornar o alt text final usado
            ) );
            // wp_die() é chamado por wp_send_json_success
        }

        /**
         * Manipula a ação de restaurar um post da lixeira (`admin_post_autobp_restore_post`).
         *
         * Verifica o nonce, ID do post e permissões do usuário (`delete_post`).
         * Utiliza `wp_untrash_post()` para mover o post da lixeira de volta ao seu status anterior
         * (geralmente 'draft'). Redireciona para a Biblioteca de Artigos com uma mensagem de feedback
         * e mantém o filtro de status da lixeira, se aplicável.
         *
         * @since 0.2.0
         * @access public
         */
        public function handle_restore_post_action() {
            $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
            $status_filter = isset( $_GET['post_status_filter'] ) ? sanitize_key( $_GET['post_status_filter'] ) : '';
            $user_id = get_current_user_id();
            AutoBP_Log_Manager::info( 'Tentativa de restaurar post.', array( 'post_id' => $post_id, 'user_id' => $user_id, 'current_filter' => $status_filter ) );

            $redirect_url = admin_url( 'admin.php?page=autobp-article-library' );
            if ( !empty($status_filter) ) {
                $redirect_url = add_query_arg( 'post_status_filter', $status_filter, $redirect_url );
            }

            if ( ! $post_id ) {
                AutoBP_Log_Manager::warning( 'Restauração falhou: ID do post inválido.', array( 'user_id' => $user_id ) );
                wp_safe_redirect( add_query_arg( array( 'autobp_message' => 'invalid_post_id', 'post_id' => 0 ), $redirect_url ) );
                exit;
            }

            check_admin_referer( 'autobp_restore_post_' . $post_id );

            if ( ! current_user_can( 'delete_post', $post_id ) ) { // Requer a mesma capacidade de mover para lixeira
                AutoBP_Log_Manager::warning( 'Restauração falhou: Permissão negada.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                wp_safe_redirect( add_query_arg( array( 'autobp_message' => 'restore_permission_denied', 'post_id' => $post_id ), $redirect_url ) );
                exit;
            }

            $result = wp_untrash_post( $post_id );

            if ( false === $result ) {
                AutoBP_Log_Manager::error( 'Falha ao restaurar post (wp_untrash_post).', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                wp_safe_redirect( add_query_arg( array( 'autobp_message' => 'restore_failed', 'post_id' => $post_id ), $redirect_url ) );
            } else {
                AutoBP_Log_Manager::info( 'Post restaurado com sucesso.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                wp_safe_redirect( add_query_arg( array( 'autobp_message' => 'post_restored', 'post_id' => $post_id ), $redirect_url ) );
            }
            exit;
        }

        /**
         * Manipula a ação de excluir um post permanentemente (`admin_post_autobp_delete_permanent_post`).
         *
         * Verifica o nonce, ID do post e permissões do usuário (`delete_post`).
         * Utiliza `wp_delete_post( $post_id, true )` para remover o post permanentemente do banco de dados.
         * O segundo parâmetro `true` força a exclusão bypassando a lixeira.
         * Redireciona para a Biblioteca de Artigos com uma mensagem de feedback e mantém o filtro de
         * status da lixeira, se aplicável. O título do post é recuperado antes da exclusão para
         * uso na mensagem de feedback.
         *
         * @since 0.2.0
         * @access public
         */
        public function handle_delete_permanent_post_action() {
            $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
            $status_filter = isset( $_GET['post_status_filter'] ) ? sanitize_key( $_GET['post_status_filter'] ) : '';
            $user_id = get_current_user_id();
            AutoBP_Log_Manager::info( 'Tentativa de excluir post permanentemente.', array( 'post_id' => $post_id, 'user_id' => $user_id, 'current_filter' => $status_filter ) );
            
            $post_title_for_feedback = get_the_title($post_id); // Get title before deleting

            $redirect_url = admin_url( 'admin.php?page=autobp-article-library' );
             if ( !empty($status_filter) ) {
                $redirect_url = add_query_arg( 'post_status_filter', $status_filter, $redirect_url );
            }

            if ( ! $post_id ) {
                AutoBP_Log_Manager::warning( 'Exclusão permanente falhou: ID do post inválido.', array( 'user_id' => $user_id ) );
                wp_safe_redirect( add_query_arg( array( 'autobp_message' => 'invalid_post_id', 'post_id' => 0 ), $redirect_url ) );
                exit;
            }

            check_admin_referer( 'autobp_delete_permanent_post_' . $post_id );

            if ( ! current_user_can( 'delete_post', $post_id ) ) { // Requer a mesma capacidade
                AutoBP_Log_Manager::warning( 'Exclusão permanente falhou: Permissão negada.', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                wp_safe_redirect( add_query_arg( array( 'autobp_message' => 'delete_permanent_permission_denied', 'post_id' => $post_id ), $redirect_url ) );
                exit;
            }

            $result = wp_delete_post( $post_id, true ); // true para forçar exclusão permanente

            if ( false === $result || null === $result ) { // wp_delete_post retorna o post deletado em sucesso, false ou null em falha
                AutoBP_Log_Manager::error( 'Falha ao excluir post permanentemente (wp_delete_post).', array( 'post_id' => $post_id, 'user_id' => $user_id ) );
                wp_safe_redirect( add_query_arg( array( 'autobp_message' => 'delete_permanent_failed', 'post_id' => $post_id ), $redirect_url ) );
            } else {
                AutoBP_Log_Manager::info( 'Post excluído permanentemente com sucesso.', array( 'post_id' => $post_id, 'user_id' => $user_id, 'deleted_post_object' => $result ) );
                // Passar o título original para a mensagem de feedback, já que o post não existe mais
                wp_safe_redirect( add_query_arg( array( 'autobp_message' => 'post_deleted_permanently', 'post_id' => $post_id, 'post_title' => urlencode($post_title_for_feedback) ), $redirect_url ) );
            }
            exit;
        }

        /**
         * Adiciona o Schema Markup (JSON-LD) do tipo `Article` ao cabeçalho de posts gerados pelo plugin.
         *
         * Este método é acionado pelo hook `wp_head`.
         * 1. Verifica se a página atual é um post singular (`is_singular('post')`).
         * 2. Obtém o ID do post atual.
         * 3. Verifica se o post foi gerado pelo plugin, checando os metadados
         *    `_autobp_generation_mode` ou `_autobp_generated_from_niche_id`.
         *    Se não for um post do plugin, o método retorna sem adicionar o schema.
         * 4. Coleta os dados necessários para o schema:
         *    - Informações do post: URL, título, data de publicação e modificação (formato ISO 8601 GMT).
         *    - Descrição: Prioriza a meta descrição personalizada (`_autobp_meta_description`).
         *      Se ausente, usa um trecho do conteúdo do post (`wp_trim_words`).
         *    - Imagem Destacada: Se existir, obtém a URL, largura e altura da imagem no tamanho 'full'.
         *    - Autor: Nome de exibição e URL do perfil do autor.
         *    - Publisher (Editor): Nome do site e URL do ícone do site (favicon) como logo.
         *    - Palavras-chave: Tags associadas ao post.
         * 5. Constrói um array `$schema` com a estrutura `Article` do Schema.org, preenchendo
         *    as propriedades `mainEntityOfPage`, `headline`, `description`, `image`, `author`,
         *    `publisher`, `datePublished`, `dateModified`, e `keywords`.
         * 6. Converte o array `$schema` para uma string JSON-LD usando `wp_json_encode()` com as flags
         *    `JSON_UNESCAPED_SLASHES`, `JSON_UNESCAPED_UNICODE`, e `JSON_PRETTY_PRINT`.
         * 7. Imprime o script JSON-LD dentro de uma tag `<script type="application/ld+json">` no `wp_head`.
         *
         * @since 0.2.0
         * @access public
         */
        public function add_article_schema_markup() {
            if ( ! is_singular( 'post' ) ) {
                return;
            }

            $post_id = get_queried_object_id();
            if ( ! $post_id ) {
                return;
            }

            // Verificar se o post foi gerado pelo plugin
            // Usamos '_autobp_generation_mode' como um indicador genérico.
            // Poderia ser mais específico se necessário (ex: '_autobp_generated_from_niche_id' OU '_autobp_generated_from_source_url')
            $generation_mode = get_post_meta( $post_id, '_autobp_generation_mode', true );
            if ( empty( $generation_mode ) ) {
                // Se não encontrou '_autobp_generation_mode', verificar por '_autobp_generated_from_niche_id' como fallback
                // para posts gerados antes da introdução de '_autobp_generation_mode'.
                if ( ! get_post_meta( $post_id, '_autobp_generated_from_niche_id', true ) ) {
                     return;
                }
            }

            $post = get_post( $post_id );
            if ( ! $post ) {
                return;
            }

            $site_name = get_bloginfo( 'name' );
            $site_url = home_url();
            $post_url = get_permalink( $post_id );
            $headline = get_the_title( $post_id );
            $date_published = get_post_time( 'c', true, $post_id ); 
            $date_modified = get_post_modified_time( 'c', true, $post_id );

            $meta_description = get_post_meta( $post_id, '_autobp_meta_description', true );
            $description = !empty($meta_description) ? esc_attr($meta_description) : esc_attr(wp_trim_words( wp_strip_all_tags( $post->post_content ), 25, '...' ));

            $image_data = array();
            if ( has_post_thumbnail( $post_id ) ) {
                $thumbnail_id = get_post_thumbnail_id( $post_id );
                $image_src_array = wp_get_attachment_image_src( $thumbnail_id, 'full' );
                if ( $image_src_array ) {
                    $image_data = array(
                        '@type'  => 'ImageObject',
                        'url'    => esc_url($image_src_array[0]),
                        'width'  => intval($image_src_array[1]),
                        'height' => intval($image_src_array[2]),
                    );
                }
            }

            $author_id = $post->post_author;
            $author_name = get_the_author_meta( 'display_name', $author_id );
            $author_url = get_author_posts_url( $author_id );


            $schema = array(
                '@context'    => 'https://schema.org',
                '@type'       => 'Article', 
                'mainEntityOfPage' => array(
                    '@type' => 'WebPage',
                    '@id'   => esc_url($post_url),
                ),
                'headline'    => esc_html($headline),
                'description' => $description, // Já escapado
                'datePublished' => $date_published,
                'dateModified'  => $date_modified,
                'author'      => array(
                    '@type' => 'Person', 
                    'name'  => esc_html($author_name),
                ),
                'publisher'   => array(
                    '@type' => 'Organization',
                    'name'  => esc_html($site_name),
                ),
            );

            if(!empty($author_url)){
                $schema['author']['url'] = esc_url($author_url);
            }

            $site_icon_url = get_site_icon_url();
            if ( !empty($site_icon_url) ) {
                 $schema['publisher']['logo'] = array(
                    '@type' => 'ImageObject',
                    'url'   => esc_url($site_icon_url),
                );
            }

            if ( ! empty( $image_data ) ) {
                $schema['image'] = $image_data;
            } else {
                // Se não houver imagem destacada, mas houver um logo do publisher, pode ser usado como imagem padrão
                // ou omitir a propriedade 'image'. Para Article, uma imagem é fortemente recomendada.
                // Se o logo do publisher foi definido, pode ser uma alternativa.
                if(isset($schema['publisher']['logo']['url']) && !empty($schema['publisher']['logo']['url'])){
                    // $schema['image'] = $schema['publisher']['logo']; // Descomentar se quiser usar logo como fallback
                }
            }
            
            // Adicionar palavras-chave (tags do post)
            $tags = get_the_tags($post_id);
            if ($tags && !is_wp_error($tags)) {
                $keywords_array = array();
                foreach ($tags as $tag) {
                    $keywords_array[] = $tag->name;
                }
                if (!empty($keywords_array)) {
                    $schema['keywords'] = esc_html(implode(', ', $keywords_array));
                }
            }


            echo "\n" . '<script type="application/ld+json">' . "\n";
            echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
            echo "\n" . '</script>' . "\n";
        }

        /**
         * Calcula a densidade de uma palavra-chave foco (que pode ser uma frase) em um texto.
         *
         * Processo:
         * 1. Valida se o conteúdo e a palavra-chave foco não estão vazios (após trim).
         * 2. Limpa o conteúdo de todas as tags HTML usando `wp_strip_all_tags()`.
         * 3. Converte tanto o conteúdo limpo quanto a palavra-chave foco para minúsculas
         *    (usando `mb_strtolower` se disponível, senão `strtolower`) para garantir
         *    comparação case-insensitive.
         * 4. Divide o conteúdo em um array de palavras usando `preg_split('/\s+/', ...)`
         *    para obter uma contagem total de palavras (`$word_count`).
         * 5. Se a contagem total de palavras for zero, retorna `0.0`.
         * 6. Conta o número de ocorrências da frase/palavra-chave foco (em minúsculas)
         *    dentro do conteúdo (em minúsculas) usando `substr_count()`.
         * 7. Se não houver ocorrências, retorna `0.0`.
         * 8. Calcula a densidade como `($keyword_occurrences / $word_count) * 100`.
         * 9. Arredonda o resultado para duas casas decimais usando `round()`.
         * 10. Loga informações de depuração sobre o cálculo.
         *
         * @since 0.2.0
         * @access public
         * @static
         * @param string $content O texto completo no qual a densidade da palavra-chave será calculada.
         * @param string $focus_keyword A palavra-chave foco (pode ser uma única palavra ou uma frase).
         * @return float|false A densidade percentual calculada (ex: 2.5 para 2.5%),
         *                     `0.0` se a palavra-chave não for encontrada ou se não houver palavras no conteúdo,
         *                     ou `false` se os dados de entrada (`$content` ou `$focus_keyword`) forem inválidos (vazios).
         */
        public static function calculate_keyword_density( $content, $focus_keyword ) {
            if ( empty( trim( $content ) ) || empty( trim( $focus_keyword ) ) ) {
                AutoBP_Log_Manager::debug('Cálculo de densidade: Conteúdo ou palavra-chave foco vazios.', array('content_empty' => empty(trim($content)), 'keyword_empty' => empty(trim($focus_keyword))));
                return false;
            }

            // Preparar o conteúdo e a palavra-chave
            $content_clean = wp_strip_all_tags( $content ); 
            $content_lower = function_exists('mb_strtolower') ? mb_strtolower( $content_clean ) : strtolower( $content_clean );
            $focus_keyword_lower = function_exists('mb_strtolower') ? mb_strtolower( trim( $focus_keyword ) ) : strtolower( trim( $focus_keyword ) );

            // Contar o número total de palavras no conteúdo
            $words_array = preg_split('/\s+/', $content_lower, -1, PREG_SPLIT_NO_EMPTY);
            $word_count = count($words_array);

            if ( $word_count === 0 ) {
                AutoBP_Log_Manager::debug('Cálculo de densidade: Contagem de palavras no conteúdo é zero.', array('focus_keyword' => $focus_keyword));
                return 0.0; 
            }

            // Contar ocorrências da palavra-chave foco
            $keyword_occurrences = substr_count( $content_lower, $focus_keyword_lower );

            if ( $keyword_occurrences === 0 ) {
                return 0.0;
            }
            
            $density = ( $keyword_occurrences / $word_count ) * 100;
            $rounded_density = round( $density, 2 );
            AutoBP_Log_Manager::debug('Densidade calculada.', array('focus_keyword' => $focus_keyword, 'occurrences' => $keyword_occurrences, 'word_count' => $word_count, 'density' => $rounded_density));

            return $rounded_density;
        }

        /**
         * Busca sugestões de links externos relevantes usando a API OpenAI.
         *
         * Este método estático envia um trecho do conteúdo do post e suas palavras-chave
         * para a API OpenAI (`gpt-3.5-turbo`) com um prompt específico para sugerir
         * 2-3 URLs externas de alta autoridade e relevância, juntamente com uma breve
         * justificativa para cada sugestão.
         *
         * O prompt instrui a IA a formatar a resposta como um objeto JSON com uma chave
         * principal `external_links`, contendo um array de objetos, cada um com as chaves
         * `url` e `reason`. Links para a Wikipedia são desencorajados no prompt.
         *
         * A função lida com a chamada à API via `wp_remote_post`, processa a resposta,
         * tenta extrair o JSON (mesmo que a IA adicione texto extra ao redor dele),
         * valida as URLs e razões, e retorna um array limitado de sugestões válidas.
         *
         * Erros durante o processo (chave de API ausente, erro na chamada HTTP,
         * erro da API OpenAI, JSON inválido) são logados e resultam em um array vazio.
         *
         * @since 0.2.0
         * @access public
         * @static
         * @param string $post_content_snippet Um trecho do conteúdo do artigo (ex: primeiros 1500 caracteres após remoção de HTML).
         * @param string $target_keywords As palavras-chave principais do artigo (string separada por vírgulas).
         * @param string $target_language O idioma do artigo (ex: 'pt-BR').
         * @param string $api_key A chave da API OpenAI.
         * @return array Um array de sugestões de links externos. Cada sugestão é um array associativo
         *               com as chaves 'url' (string) e 'reason' (string). Retorna um array vazio
         *               em caso de falha, erro, ou se nenhuma sugestão válida for encontrada.
         */
        public static function fetch_external_link_suggestions( $post_content_snippet, $target_keywords, $target_language, $api_key ) {
            if ( empty( $api_key ) || empty( $post_content_snippet ) || empty( $target_keywords ) ) {
                AutoBP_Log_Manager::warning( 'Links Externos: API Key, conteúdo ou palavras-chave ausentes para a busca.', 
                    array('has_api_key' => !empty($api_key), 'content_empty' => empty($post_content_snippet), 'keywords_empty' => empty($target_keywords) ) );
                return array();
            }

            $stripped_content_snippet = wp_strip_all_tags($post_content_snippet);
            $trimmed_snippet = mb_substr( $stripped_content_snippet, 0, 1500 );

            $prompt = sprintf(
                "Para um artigo em '%s' sobre '%s', cujo início é:\n\"%s...\"\n\nSugira 2-3 URLs externas (links para outros websites) que sejam de alta autoridade, confiáveis e forneçam informações complementares ou fontes valiosas relacionadas ao tema principal. Não sugira links para a Wikipedia. Para cada sugestão, forneça a URL completa e uma breve frase explicando por que ela é relevante (reason).\nFormate a resposta EXCLUSIVAMENTE como um objeto JSON com uma chave principal \"external_links\", que contém um array de objetos. Cada objeto deve ter as chaves \"url\" e \"reason\". Exemplo: {\"external_links\": [{\"url\": \"https://www.example.com/article\", \"reason\": \"Fornece dados estatísticos detalhados sobre o tópico.\"}]}",
                esc_html( $target_language ), // Escapando para o prompt, embora a API não renderize HTML.
                esc_html( $target_keywords ),
                esc_html( $trimmed_snippet ) 
            );

            $api_body = array(
                'model'    => 'gpt-3.5-turbo',
                'messages' => array(
                    array('role' => 'system', 'content' => 'Você é um assistente de pesquisa especialista em encontrar links externos relevantes e de autoridade. Responda APENAS com o objeto JSON solicitado.'),
                    array('role' => 'user', 'content' => $prompt)
                ),
                'max_tokens'  => 350, // Aumentado um pouco para garantir espaço para JSON e razões.
                'temperature' => 0.4,
            );

            AutoBP_Log_Manager::info( 'Buscando sugestões de links externos via OpenAI.', array( 'keywords' => $target_keywords, 'lang' => $target_language, 'snippet_length' => mb_strlen($trimmed_snippet) ) );
            
            $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
                'body'    => json_encode( $api_body ), // Usar json_encode aqui
                'headers' => array( 'Authorization' => 'Bearer ' . $api_key, 'Content-Type'  => 'application/json' ),
                'timeout' => 90,
            ) );

            if ( is_wp_error( $response ) ) {
                AutoBP_Log_Manager::error( 'Links Externos: Erro wp_remote_post OpenAI: ' . $response->get_error_message(), array( 'keywords' => $target_keywords, 'error_code' => $response->get_error_code() ) );
                return array();
            }
            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body_raw = wp_remote_retrieve_body( $response );

            if ( $response_code !== 200 ) {
                AutoBP_Log_Manager::error( 'Links Externos: Erro API OpenAI.', array( 'code' => $response_code, 'body_snippet' => mb_substr($response_body_raw, 0, 500), 'keywords' => $target_keywords ) );
                return array();
            }
            
            $response_data = json_decode( $response_body_raw, true );
            $message_content = isset( $response_data['choices'][0]['message']['content'] ) ? $response_data['choices'][0]['message']['content'] : null;

            if ( $message_content ) {
                $json_start = strpos($message_content, '{');
                $json_end = strrpos($message_content, '}');

                if ($json_start !== false && $json_end !== false && $json_end > $json_start) {
                    $json_string = substr($message_content, $json_start, ($json_end - $json_start) + 1);
                    $suggestions_data = json_decode( $json_string, true );

                    if ( json_last_error() === JSON_ERROR_NONE && isset( $suggestions_data['external_links'] ) && is_array( $suggestions_data['external_links'] ) ) {
                        $valid_suggestions = array_filter($suggestions_data['external_links'], function($link){
                            return !empty($link['url']) && filter_var($link['url'], FILTER_VALIDATE_URL) !== false && !empty($link['reason']);
                        });
                        $final_suggestions = array_slice($valid_suggestions, 0, 3); // Limitar a 3 sugestões
                        AutoBP_Log_Manager::info( sprintf('%d sugestões de links externos válidas encontradas e processadas.', count($final_suggestions)), array( 'keywords' => $target_keywords, 'suggestions' => $final_suggestions) );
                        return $final_suggestions;
                    } else {
                         AutoBP_Log_Manager::warning( 'Links Externos: Falha ao decodificar JSON da resposta da IA ou formato inesperado.', array('raw_ai_response_snippet' => mb_substr($message_content,0,500), 'json_error' => json_last_error_msg()) );
                    }
                } else {
                    AutoBP_Log_Manager::warning( 'Links Externos: JSON não encontrado na resposta da IA.', array('raw_ai_response_snippet' => mb_substr($message_content,0,500)) );
                }
            } else {
                 AutoBP_Log_Manager::warning( 'Links Externos: Conteúdo da mensagem da IA vazio ou não encontrado.', array('response_body_snippet' => mb_substr($response_body_raw,0,500)) );
            }
            return array();
        }

        /**
         * Tenta preencher campos de plugins de SEO (Yoast SEO, Rank Math) com a palavra-chave foco
         * e a meta descrição geradas pelo AutoBlogPro.
         *
         * Este método estático é chamado após a geração do conteúdo do post, da palavra-chave foco
         * e da meta descrição.
         * 1. Verifica as opções do plugin para determinar qual plugin de SEO está ativo
         *    (`autobp_active_seo_plugin`: 'none', 'yoast', 'rankmath') e se o preenchimento
         *    automático está habilitado (`autobp_seo_plugin_fill_fields`).
         * 2. Se a funcionalidade estiver desabilitada ou nenhum plugin selecionado, retorna.
         * 3. Valida se `$post_id`, `$focus_keyword` e `$meta_description` não estão vazios.
         * 4. Sanitiza `$focus_keyword` e `$meta_description`.
         * 5. Com base no plugin de SEO ativo selecionado:
         *    - **Yoast SEO:** Verifica se o plugin Yoast SEO está ativo (`is_plugin_active` ou `class_exists`).
         *      Se sim, atualiza os metadados do post:
         *      - `_yoast_wpseo_focuskw` com `$focus_keyword`.
         *      - `_yoast_wpseo_metadesc` com `$meta_description`.
         *    - **Rank Math:** Verifica se o plugin Rank Math está ativo (`is_plugin_active` ou `class_exists`).
         *      Se sim, atualiza os metadados do post:
         *      - `rank_math_focus_keyword` com `strtolower($focus_keyword)` (Rank Math geralmente armazena em minúsculas).
         *      - `rank_math_description` com `$meta_description`.
         * 6. Loga as ações de preenchimento ou avisos se os plugins configurados não estiverem ativos.
         *
         * @since 0.2.0
         * @access public
         * @static
         * @param int    $post_id ID do post cujos campos de SEO devem ser preenchidos.
         * @param string $focus_keyword A palavra-chave foco a ser definida.
         * @param string $meta_description A meta descrição a ser definida.
         */
        public static function maybe_fill_seo_plugin_fields( $post_id, $focus_keyword, $meta_description ) {
            $active_seo_plugin = get_option( 'autobp_active_seo_plugin', 'none' );
            $fill_fields_enabled = get_option( 'autobp_seo_plugin_fill_fields', 0 );

            if ( ! $fill_fields_enabled || $active_seo_plugin === 'none' ) {
                return;
            }

            if ( empty( $post_id ) || empty( $focus_keyword ) || empty( $meta_description ) ) {
                AutoBP_Log_Manager::warning( 'Preenchimento SEO: Dados insuficientes (post ID, keyword ou meta desc).', 
                    array(
                        'post_id' => $post_id, 
                        'has_keyword' => !empty($focus_keyword), 
                        'has_meta_desc' => !empty($meta_description)
                    ) 
                );
                return;
            }
            
            $focus_keyword = sanitize_text_field( $focus_keyword );
            $meta_description = sanitize_text_field( $meta_description );

            AutoBP_Log_Manager::info( "Tentando preencher campos do plugin SEO '{$active_seo_plugin}' para o post ID {$post_id}.", 
                array(
                    'post_id' => $post_id,
                    'focus_kw_length' => strlen($focus_keyword), 
                    'meta_desc_length' => strlen($meta_description)
                ) 
            );

            if ( $active_seo_plugin === 'yoast' ) {
                if ( is_plugin_active('wordpress-seo/wp-seo.php') || class_exists('WPSEO_Meta') ) {
                    update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_keyword );
                    update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_description );
                    AutoBP_Log_Manager::info( "Campos Yoast SEO atualizados para post ID {$post_id}." );
                } else {
                    AutoBP_Log_Manager::warning( "Configurado para Yoast, mas plugin Yoast SEO não parece estar ativo. Post ID {$post_id}." );
                }
            } elseif ( $active_seo_plugin === 'rankmath' ) {
                if ( is_plugin_active('seo-by-rank-math/rank-math.php') || class_exists('RankMath') ) {
                    // Rank Math pode armazenar a keyword foco como uma string separada por vírgulas e em minúsculas.
                    // Se $focus_keyword já for uma frase, ela será usada como está. Se for uma keyword única, será convertida para minúsculas.
                    // Para múltiplas, o ideal seria já ter um array e converter para string minúscula.
                    // Por ora, vamos assumir que $focus_keyword é a principal e única.
                    update_post_meta( $post_id, 'rank_math_focus_keyword', strtolower( $focus_keyword ) );
                    update_post_meta( $post_id, 'rank_math_description', $meta_description );
                    AutoBP_Log_Manager::info( "Campos Rank Math atualizados para post ID {$post_id}." );
                } else {
                    AutoBP_Log_Manager::warning( "Configurado para Rank Math, mas plugin Rank Math não parece estar ativo. Post ID {$post_id}." );
                }
            }
        }

        /**
         * Manipula a ação de zerar a contagem de tokens OpenAI usados.
         *
         * Esta função é chamada via `admin_post_autobp_reset_openai_token_count` quando o usuário
         * clica no botão "Zerar Contagem de Tokens" na página de Configurações.
         *
         * Lógica:
         * 1. Verifica o nonce `autobp_reset_openai_token_count_nonce` para segurança.
         * 2. Verifica se o usuário atual possui a capacidade `manage_options`.
         * 3. Atualiza a opção `autobp_openai_total_tokens_used` para 0.
         * 4. Registra um evento no sistema de logs.
         * 5. Redireciona o usuário de volta para a página "Configurações" com uma mensagem de sucesso.
         *
         * @since 0.2.0
         * @access public
         */
        public function handle_reset_openai_token_count_action() {
            check_admin_referer( 'autobp_reset_openai_token_count_action', 'autobp_reset_openai_token_count_nonce' );

            if ( ! current_user_can( 'manage_options' ) ) {
                AutoBP_Log_Manager::warning( 'Tentativa de zerar contagem de tokens OpenAI falhou: Permissão negada.', array( 'user_id' => get_current_user_id() ) );
                wp_die( __( 'Você não tem permissão para realizar esta ação.', 'autoblogpro' ) );
            }

            update_option( 'autobp_openai_total_tokens_used', 0 );
            AutoBP_Log_Manager::info( 'Contagem de tokens OpenAI zerada pelo usuário.', array( 'user_id' => get_current_user_id() ) );
            
            $redirect_url = admin_url( 'admin.php?page=autobp-settings&autobp_message=tokens_reset' );
            wp_safe_redirect( $redirect_url );
            exit;
        }

    }

    // Registrar hooks de ativação e desativação
    register_activation_hook( AUTOBP_PLUGIN_FILE, array( 'AutoBlogPro', 'activate' ) );
    register_deactivation_hook( AUTOBP_PLUGIN_FILE, array( 'AutoBlogPro', 'deactivate' ) );

    // Inicializar o plugin
    // A função get_instance() garante que o construtor seja chamado apenas uma vez.
    // e dentro do construtor, o add_action para 'admin_post_autobp_publish_post' será configurado.
    AutoBlogPro::get_instance();
}
?>
