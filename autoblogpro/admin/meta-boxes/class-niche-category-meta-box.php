<?php
/**
 * Gerencia a meta box para associar categorias do WordPress ao CPT 'Nicho'.
 *
 * Esta classe é responsável por adicionar a interface da meta box na tela de edição
 * do CPT 'niche', renderizar os checkboxes das categorias e salvar
 * as seleções no banco de dados.
 *
 * @package     AutoBlogPro
 * @subpackage  Admin/Meta_Boxes
 * @since       0.1.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Classe Niche_Category_Meta_Box.
 * 
 * Lida com a criação, exibição e salvamento da metabox de categorias para nichos.
 */
class Niche_Category_Meta_Box {

    /**
     * Chave do meta field usado para armazenar os IDs das categorias associadas ao nicho.
     *
     * @since 0.1.0
     * @access private
     * @var string
     */
    private static $meta_key = '_niche_associated_categories';

    /**
     * Inicializa os hooks do WordPress necessários para a metabox.
     *
     * Adiciona actions para 'add_meta_boxes' e 'save_post_niche'.
     *
     * @since 0.1.0
     * @access public
     * @static
     */
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add' ) );
        add_action( 'save_post_niche', array( __CLASS__, 'save' ) ); // Hook específico para o CPT 'niche'
    }

    /**
     * Adiciona a metabox à tela de edição do CPT 'niche'.
     *
     * Chamado pelo hook 'add_meta_boxes'.
     *
     * @since 0.1.0
     * @access public
     * @static
     * @param string $post_type O tipo de post atual. (Embora o hook 'add_meta_boxes_niche' pudesse ser usado, este método pode ser chamado genericamente e filtrar internamente se necessário, ou confiar no hook específico do 'add_meta_boxes').
     */
    public static function add( $post_type ) {
        // Verifica se estamos no CPT 'niche' antes de adicionar a metabox, 
        // embora o add_action('add_meta_boxes_niche', ...) seria mais direto.
        // Se usando add_action('add_meta_boxes', ...), esta verificação é útil.
        // Para esta implementação, o hook save_post_niche já garante o CPT no save.
        // E add_meta_box é chamado no contexto onde $post_type é 'niche' se o hook for add_meta_boxes_niche
        // ou se for 'add_meta_boxes' e queremos adicionar apenas para 'niche'.
        // Para simplificar, assumimos que este método é chamado no contexto certo (ex: via add_meta_boxes_niche ou uma verificação anterior).
        // A instrução original era apenas adicionar a metabox, o que é feito abaixo.
        add_meta_box(
            'niche_categories_meta_box', // ID da metabox
            __( 'Categorias Associadas', 'autoblogpro' ),
            array( __CLASS__, 'render' ), // Callback para renderizar o conteúdo
            'niche',                      // Slug do Custom Post Type ('niche')
            'side',                       // Contexto (normal, side, advanced)
            'default'                     // Prioridade (high, core, default, low)
        );
    }

    /**
     * Renderiza o conteúdo HTML da metabox.
     *
     * Exibe uma lista de checkboxes, cada um representando uma categoria do WordPress.
     * As categorias já associadas ao nicho atual são marcadas.
     *
     * @since 0.1.0
     * @access public
     * @static
     * @param WP_Post $post O objeto do post (nicho) atual.
     */
    public static function render( $post ) {
        // Adiciona um nonce field para verificação de segurança no momento do salvamento.
        wp_nonce_field( 'niche_category_nonce_action', 'niche_category_nonce_field' );

        // Obtém todas as categorias do WordPress.
        $categories = get_categories( array( 'hide_empty' => 0 ) );

        // Obtém as categorias já associadas a este nicho (post).
        $selected_categories = get_post_meta( $post->ID, self::$meta_key, true );
        // Garante que $selected_categories seja um array para evitar erros com in_array.
        if ( ! is_array( $selected_categories ) ) {
            $selected_categories = empty( $selected_categories ) ? array() : (array) $selected_categories;
        }

        echo '<ul>';
        foreach ( $categories as $category ) {
            $checked = in_array( $category->term_id, $selected_categories ) ? 'checked="checked"' : '';
            echo '<li>';
            echo '<label>';
            echo '<input type="checkbox" name="niche_associated_categories[]" value="' . esc_attr( $category->term_id ) . '" ' . $checked . '>';
            echo esc_html( $category->name );
            echo '</label>';
            echo '</li>';
        }
        echo '</ul>';
    }

    /**
     * Salva os dados da metabox quando o post (nicho) é salvo.
     *
     * Chamado pelo hook 'save_post_niche'.
     * Realiza verificações de segurança (nonce, permissões) antes de salvar.
     * Sanitiza os dados recebidos antes de atualizar o post meta.
     *
     * @since 0.1.0
     * @access public
     * @static
     * @param int $post_id O ID do post (nicho) que está sendo salvo.
     */
    public static function save( $post_id ) {
        // 1. Verifica o nonce.
        // O nonce é esperado no array $_POST.
        // sanitize_text_field e wp_unslash são usados para limpar o valor do nonce antes da verificação.
        if ( ! isset( $_POST['niche_category_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['niche_category_nonce_field'] ) ), 'niche_category_nonce_action' ) ) {
            return; // Sai se o nonce não for válido ou não estiver presente.
        }

        // 2. Verifica se o usuário atual tem permissão para editar o post.
        // 'edit_post' é uma capacidade genérica, pode ser trocada por 'edit_specific_post_type' se necessário.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return; // Sai se o usuário não tiver permissão.
        }

        // 3. Não salva durante o autosave do WordPress.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return; // Sai se for um autosave.
        }

        // 4. Verifica se o post que está sendo salvo é uma revisão.
        // Geralmente não queremos salvar metadados para revisões.
        if ( wp_is_post_revision( $post_id ) ) {
            return; // Sai se for uma revisão.
        }
        
        // 5. Verifica se o tipo de post é 'niche'.
        // Embora o hook 'save_post_niche' já restrinja a este CPT,
        // esta é uma verificação de segurança adicional.
        if ( get_post_type( $post_id ) !== 'niche' ) {
            return; // Sai se não for o CPT 'niche'.
        }

        // Processa e salva os dados.
        // Espera-se que os IDs das categorias venham como um array de 'niche_associated_categories'.
        if ( isset( $_POST['niche_associated_categories'] ) && is_array( $_POST['niche_associated_categories'] ) ) {
            // Sanitiza cada ID de categoria para garantir que são inteiros.
            $sanitized_categories = array_map( 'intval', $_POST['niche_associated_categories'] );
            update_post_meta( $post_id, self::$meta_key, $sanitized_categories );
        } else {
            // Se nenhum checkbox estiver marcado (ou o campo não for enviado),
            // remove o metadado para limpar quaisquer associações anteriores.
            delete_post_meta( $post_id, self::$meta_key );
        }
    }
}
?>
