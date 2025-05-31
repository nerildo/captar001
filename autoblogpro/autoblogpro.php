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
         *
         * @since   0.1.0
         * @access  private
         */
        private function __construct() {
            $this->version = AUTOBP_VERSION;
            // Ações de inicialização aqui
            add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
            add_action( 'init', array( $this, 'register_niche_cpt' ) );
            add_action( 'admin_init', array( $this, 'load_admin_dependencies' ) );
            add_action( 'admin_post_autobp_publish_post', array( $this, 'handle_publish_post_action' ) );
        }

                /**
                 * Carrega dependências da área administrativa.
                 * @since 0.1.0
                 */
                public function load_admin_dependencies() {
                    require_once AUTOBP_PLUGIN_DIR . 'admin/meta-boxes/class-niche-category-meta-box.php';
                    Niche_Category_Meta_Box::init();

                    require_once AUTOBP_PLUGIN_DIR . 'admin/class-autobp-settings-page.php';
                    AutoBP_Settings_Page::init();
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
         * Pode ser usado para configurar opções padrão, criar tabelas, etc.
         * Por exemplo, registrar CPTs e fazer flush das regras de reescrita.
         *
         * @since   0.1.0
         * @access  public
         * @static
         */
        public static function activate() {
            // Código a ser executado na ativação
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
         * Este método é registrado no hook `admin_post_{action}`. Ele executa as seguintes etapas:
         * 1. Valida o `post_id` recebido via `$_GET`.
         * 2. Verifica o nonce de segurança (`_wpnonce`) usando `check_admin_referer()`.
         * 3. Verifica se o usuário atual tem a capacidade de 'publish_post' para o post especificado.
         * 4. Se todas as verificações passarem, atualiza o status do post para 'publish' usando `wp_update_post()`.
         * 5. Redireciona o usuário de volta para a página da Biblioteca de Artigos (`autobp-article-library`)
         *    com parâmetros de query (`autobp_message` e `post_id`) para exibir uma mensagem de
         *    sucesso ou falha na interface.
         *
         * Se qualquer verificação falhar (ID do post inválido, nonce inválido, falta de permissão),
         * o script redireciona com uma mensagem de erro apropriada ou `wp_die()` é chamado por `check_admin_referer`.
         *
         * @since 0.1.0
         * @access public
         */
        public function handle_publish_post_action() {
            // 1. Valida o post_id.
            $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
            $redirect_url = admin_url( 'admin.php?page=autobp-article-library' ); // URL base para redirecionamento.

            if ( ! $post_id ) {
                // Se o post_id for inválido ou não fornecido, redireciona com mensagem de erro.
                $redirect_url = add_query_arg( array( 'autobp_message' => 'invalid_post_id', 'post_id' => 0 ), $redirect_url );
                wp_safe_redirect( $redirect_url );
                exit;
            }

            // 2. Verifica o nonce de segurança.
            // A função `check_admin_referer` interrompe a execução com `wp_die()` se o nonce falhar.
            check_admin_referer( 'autobp_publish_post_' . $post_id );

            // 3. Verifica a capacidade (permissão) do usuário.
            if ( ! current_user_can( 'publish_post', $post_id ) ) {
                // Se o usuário não tiver permissão, redireciona com mensagem de erro.
                $redirect_url = add_query_arg( array( 'autobp_message' => 'publish_permission_denied', 'post_id' => $post_id ), $redirect_url );
                wp_safe_redirect( $redirect_url );
                exit;
                // Alternativamente, poderia usar wp_die para uma interrupção mais abrupta:
                // wp_die( __( 'Você não tem permissão para publicar este post.', 'autoblogpro' ), __( 'Erro de Permissão', 'autoblogpro' ), array( 'response' => 403, 'back_link' => true ) );
            }

            // 4. Tenta publicar o post.
            // `wp_update_post` é usado para alterar o status do post para 'publish'.
            // O segundo parâmetro `true` faz com que a função retorne `WP_Error` em caso de falha.
            $updated_post_result = wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ), true );

            // 5. Prepara o redirecionamento com base no resultado da publicação.
            if ( is_wp_error( $updated_post_result ) ) {
                // Se `wp_update_post` falhar. Isso é raro se o post existe e o usuário tem permissão,
                // mas pode acontecer devido a hooks ou outros problemas.
                $redirect_url = add_query_arg( array( 'autobp_message' => 'publish_failed', 'post_id' => $post_id ), $redirect_url );
            } else {
                // Se a publicação for bem-sucedida.
                $redirect_url = add_query_arg( array( 'autobp_message' => 'post_published', 'post_id' => $post_id ), $redirect_url );
            }

            wp_safe_redirect( $redirect_url ); // Redireciona o usuário.
            exit; // Garante que nenhum outro código seja executado após o redirecionamento.
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
