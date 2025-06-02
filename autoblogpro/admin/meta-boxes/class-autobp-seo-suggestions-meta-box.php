<?php
/**
 * Classe AutoBP_SEO_Suggestions_Meta_Box.
 *
 * Responsável por adicionar e renderizar uma metabox na tela de edição de posts
 * para exibir as sugestões de SEO geradas pelo plugin AutoBlogPro.
 *
 * @package     AutoBlogPro
 * @subpackage  Admin/Meta_Boxes
 * @since       0.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AutoBP_SEO_Suggestions_Meta_Box' ) ) {

    class AutoBP_SEO_Suggestions_Meta_Box {

        /**
         * Inicializa os hooks para adicionar a metabox.
         *
         * @since 0.2.0
         */
        public static function init() {
            add_action( 'add_meta_boxes', array( __CLASS__, 'add' ) );
            // Hook para enfileirar JS para o botão de copiar
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
        }

        /**
         * Adiciona a metabox à tela de edição de posts se o post foi gerado pelo plugin.
         *
         * @since 0.2.0
         * @param string $post_type O tipo de post atual.
         * @param WP_Post $post O objeto do post atual.
         */
        public static function add( $post_type, $post ) {
            if ( 'post' === $post_type ) {
                // Verificar se o post foi gerado pelo plugin
                $generation_mode = get_post_meta( $post->ID, '_autobp_generation_mode', true );
                $is_plugin_generated = ! empty( $generation_mode );

                // Fallback para posts mais antigos que podem não ter '_autobp_generation_mode'
                if ( ! $is_plugin_generated ) {
                    $is_plugin_generated = get_post_meta( $post->ID, '_autobp_generated_from_niche_id', true );
                }
                 if ( ! $is_plugin_generated ) {
                    $is_plugin_generated = get_post_meta( $post->ID, '_autobp_gen_params_original_text', true ); // Indicador de modo URL
                }


                if ( $is_plugin_generated ) {
                    add_meta_box(
                        'autobp_seo_suggestions_mb',
                        __( 'Sugestões de Otimização SEO (AutoBlogPro)', 'autoblogpro' ),
                        array( __CLASS__, 'render_meta_box_content' ),
                        'post',
                        'normal', // 'normal' ou 'side'
                        'high'    // 'high', 'core', 'default', 'low'
                    );
                }
            }
        }
        
        /**
         * Enfileira scripts para a metabox.
         * 
         * Atualmente, adiciona um script inline no rodapé do admin para a funcionalidade
         * do botão "Copiar" da meta descrição. Este script só é adicionado nas páginas
         * de edição de post (`post.php`, `post-new.php`) e se o post for identificado
         * como gerado pelo AutoBlogPro.
         *
         * @since 0.2.0
         * @access public
         * @static
         * @param string $hook O hook da página de administração atual.
         */
        public static function enqueue_scripts( $hook ) {
            global $post;
            // Só enfileirar na tela de edição de posts
            if ( $hook == 'post.php' || $hook == 'post-new.php' ) {
                // Verifica se o post é relevante para a metabox antes de adicionar o script.
                // A mesma lógica de verificação em `add()` pode ser usada aqui.
                $is_plugin_generated = false;
                if ($post) {
                    $generation_mode = get_post_meta( $post->ID, '_autobp_generation_mode', true );
                    $is_plugin_generated = ! empty( $generation_mode );
                    if ( ! $is_plugin_generated ) {
                        $is_plugin_generated = get_post_meta( $post->ID, '_autobp_generated_from_niche_id', true );
                    }
                    if ( ! $is_plugin_generated ) {
                        $is_plugin_generated = get_post_meta( $post->ID, '_autobp_gen_params_original_text', true );
                    }
                }

                if ( $is_plugin_generated ) {
                    add_action('admin_footer', array(__CLASS__, 'render_copy_script'));
                }
            }
        }

        /**
         * Renderiza o script JavaScript inline para a funcionalidade do botão "Copiar".
         * 
         * O script usa jQuery e a API Clipboard (`navigator.clipboard.writeText`) para copiar
         * o texto da meta descrição sugerida. Inclui um fallback simples para navegadores mais antigos
         * e exibe uma mensagem de feedback ("Copiado!") ou um alerta em caso de erro.
         * Este método é chamado via `admin_footer` se as condições em `enqueue_scripts` forem atendidas.
         *
         * @since 0.2.0
         * @access public
         * @static
         */
        public static function render_copy_script() {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Handler para copiar meta descrição
                $('body').on('click', '.autobp-copy-meta-desc', function() {
                   var targetSelector = $(this).data('target');
                   var textToCopy = $(targetSelector).text(); 
                   if (navigator.clipboard && navigator.clipboard.writeText) {
                       navigator.clipboard.writeText(textToCopy).then(() => {
                           var $feedback = $(this).next('.autobp-copy-feedback');
                           $feedback.fadeIn().delay(1500).fadeOut();
                       }).catch(err => {
                           console.error('AutoBlogPro - Erro ao copiar meta descrição: ', err);
                           alert('<?php echo esc_js(__('Falha ao copiar. Tente manualmente.', 'autoblogpro')); ?>');
                       });
                   } else {
                       // Fallback muito simples para navegadores sem clipboard API (raro hoje em dia)
                       $(targetSelector).select(); // Não funciona bem para <p>, ideal seria um textarea temporário
                       try {
                           document.execCommand('copy');
                           var $feedback = $(this).next('.autobp-copy-feedback');
                           $feedback.fadeIn().delay(1500).fadeOut();
                       } catch (err) {
                           console.error('AutoBlogPro - Erro ao copiar (fallback): ', err);
                           alert('<?php echo esc_js(__('Falha ao copiar. Tente manualmente.', 'autoblogpro')); ?>');
                       }
                   }
                });

                // Handler para usar título sugerido
                $('body').on('click', '.autobp-use-suggested-title', function() {
                    var suggestedTitle = $(this).data('suggested-title');
                    var $titleInput = $('#title'); // ID padrão do campo de título do post no WordPress
                    var $parentLi = $(this).closest('li');

                    if ( $titleInput.length && suggestedTitle ) {
                        $titleInput.val( suggestedTitle ).focus(); // Define o valor e foca no campo
                        
                        // Tenta atualizar o estado do editor Gutenberg
                        var editorDispatch = typeof wp !== 'undefined' && wp.data && typeof wp.data.dispatch === 'function' ? wp.data.dispatch("core/editor") : null;
                        if (editorDispatch) {
                            editorDispatch.editPost({ title: suggestedTitle });
                        } else {
                            // Para o editor clássico, o .val() já deve ser suficiente, mas 'input' pode ajudar alguns scripts.
                             $titleInput.trigger('input').trigger('change'); // Adicionado 'change'
                        }
                        
                        // Feedback visual
                        $('.autobp-title-set-feedback').remove(); // Remove feedbacks anteriores
                        $parentLi.append('<span class="autobp-title-set-feedback" style="margin-left:10px; color:green;"><?php echo esc_js(__('Título atualizado no campo acima! Salve o post para aplicar.', 'autoblogpro')); ?></span>');
                        $('.autobp-title-set-feedback').delay(3500).fadeOut(function() { $(this).remove(); });
                        
                        // Desabilitar todos os botões "Usar este título" na lista
                        $(this).closest('ul').find('.autobp-use-suggested-title').prop('disabled', true);
                        // $(this).prop('disabled', false); // Opcional: manter o clicado habilitado se quiser permitir mudar de ideia rapidamente
                                                            // ou desabilitar todos como está acima.
                    } else {
                        if (!$titleInput.length) {
                             console.warn('AutoBlogPro: Campo de título do post (#title) não encontrado.');
                        }
                        if (!suggestedTitle) {
                            console.warn('AutoBlogPro: Título sugerido está vazio ou não definido no atributo data.');
                        }
                    }
                });

                // Handler para copiar HTML do link interno
                $('body').on('click', '.autobp-copy-internal-link-html', function() {
                    var htmlLinkToCopy = $(this).data('html-link');
                    var $feedback = $(this).next('.autobp-copy-feedback');

                    if (htmlLinkToCopy) {
                        navigator.clipboard.writeText(htmlLinkToCopy).then(() => {
                            // Mostra feedback de sucesso, depois esconde
                            $feedback.text('<?php echo esc_js(__('HTML Copiado!', 'autoblogpro')); ?>').css('color', 'green').fadeIn().delay(1500).fadeOut();
                        }).catch(err => {
                            console.error('AutoBlogPro: Erro ao copiar HTML do link interno: ', err);
                            // Mostra feedback de erro, depois esconde e reseta o texto/cor
                            $feedback.text('<?php echo esc_js(__('Falha ao copiar!', 'autoblogpro')); ?>').css('color', 'red').fadeIn().delay(2000).fadeOut(function() {
                                $(this).text('<?php echo esc_js(__('HTML Copiado!', 'autoblogpro')); ?>').css('color', 'green'); // Reset para a próxima vez
                            });
                        });
                    } else {
                        console.warn('AutoBlogPro: Nenhum HTML de link interno para copiar.');
                         $feedback.text('<?php echo esc_js(__('Nenhum HTML para copiar.', 'autoblogpro')); ?>').css('color', 'orange').fadeIn().delay(2000).fadeOut(function() {
                            $(this).text('<?php echo esc_js(__('HTML Copiado!', 'autoblogpro')); ?>').css('color', 'green'); // Reset
                        });
                    }
                });
            });
            </script>
            <?php
        }


        /**
         * Renderiza o conteúdo HTML da metabox de Sugestões de SEO.
         *
         * Exibe várias seções com informações de SEO geradas pelo plugin, recuperadas
         * dos metadados do post:
         * 1.  **Meta Descrição Sugerida:** Exibe o texto da meta descrição (`_autobp_meta_description`)
         *     e um botão "Copiar" que utiliza JavaScript para copiar o texto.
         * 2.  **Palavra-chave Foco:** Mostra a palavra-chave foco principal (`_autobp_focus_keyword`)
         *     e sua densidade percentual calculada (`_autobp_keyword_density`).
         * 3.  **Sugestões de Títulos Alternativos:** Lista as sugestões de títulos (`_autobp_title_suggestions`).
         * 4.  **Sugestões de Links Internos:** Lista as sugestões de links internos (`_autobp_internal_link_suggestions`),
         *     exibindo o texto âncora e a URL do post sugerido.
         * 5.  **Sugestões de Links Externos (Fontes):** Lista as sugestões de links externos
         *     (`_autobp_external_link_suggestions`), exibindo a URL e a razão/justificativa para cada link.
         *
         * Se alguma informação não estiver disponível, uma mensagem apropriada é exibida para essa seção.
         *
         * @since 0.2.0
         * @access public
         * @static
         * @param WP_Post $post O objeto do post atual, passado pelo WordPress ao renderizar a metabox.
         */
        public static function render_meta_box_content( $post ) {
            // Nonce field para segurança, embora não estejamos salvando nada aqui, é uma boa prática se fosse o caso.
            // wp_nonce_field( 'autobp_seo_suggestions_meta_box', 'autobp_seo_suggestions_nonce' );

            echo '<div class="autobp-seo-suggestions-wrapper">';

            // 1. Meta Descrição
            $meta_desc = get_post_meta( $post->ID, '_autobp_meta_description', true );
            echo '<h4>' . esc_html__( 'Meta Descrição Sugerida:', 'autoblogpro' ) . '</h4>';
            if ( !empty($meta_desc) ) {
                echo '<p id="autobp-meta-desc-text-' . esc_attr($post->ID) . '">' . nl2br(esc_html($meta_desc)) . '</p>';
                echo '<button type="button" class="button button-small autobp-copy-meta-desc" data-target="#autobp-meta-desc-text-' . esc_attr($post->ID) . '">' . esc_html__('Copiar Meta Descrição', 'autoblogpro') . '</button>';
                echo '<span class="autobp-copy-feedback" style="margin-left:10px; color:green; display:none;">' . esc_html__('Copiado!', 'autoblogpro') . '</span>';
            } else {
                echo '<p>' . esc_html__( 'Nenhuma meta descrição foi gerada para este post.', 'autoblogpro' ) . '</p>';
            }
            echo '<hr style="margin:15px 0;">';

            // 2. Palavra-chave Foco e Densidade
            $focus_kw = get_post_meta( $post->ID, '_autobp_focus_keyword', true );
            $density = get_post_meta( $post->ID, '_autobp_keyword_density', true );
            echo '<h4>' . esc_html__( 'Palavra-chave Foco:', 'autoblogpro' ) . '</h4>';
            if ( !empty($focus_kw) ) {
                echo '<p>' . esc_html($focus_kw) . ' (<strong>' . esc_html__( 'Densidade:', 'autoblogpro' ) . '</strong> ' . esc_html(number_format_i18n( (float)$density, 2 )) . '%)</p>';
            } else {
                echo '<p>' . esc_html__( 'Nenhuma palavra-chave foco principal foi definida ou calculada.', 'autoblogpro' ) . '</p>';
            }
            echo '<hr style="margin:15px 0;">';

            // 3. Sugestões de Títulos
            $title_suggestions = get_post_meta( $post->ID, '_autobp_title_suggestions', true );
            echo '<h4>' . esc_html__( 'Sugestões de Títulos Alternativos:', 'autoblogpro' ) . '</h4>';
            if ( !empty($title_suggestions) && is_array($title_suggestions) ) {
                echo '<ul id="autobp-title-suggestions-list-' . esc_attr($post->ID) . '">';
                foreach ( $title_suggestions as $index => $suggested_title ) {
                    if ( !empty(trim($suggested_title)) ) {
                        echo '<li>';
                        echo '<span id="autobp-suggested-title-' . esc_attr($post->ID) . '-' . esc_attr($index) . '">' . esc_html( $suggested_title ) . '</span>';
                        echo ' <button type="button" class="button button-small autobp-use-suggested-title" 
                                    data-postid="' . esc_attr($post->ID) . '" 
                                    data-title-index="' . esc_attr($index) . '"
                                    data-suggested-title="' . esc_attr( $suggested_title ) . '">';
                        echo esc_html__( 'Usar este título', 'autoblogpro' );
                        echo '</button>';
                        echo '</li>';
                    }
                }
                echo '</ul>';
            } else {
                echo '<p>' . esc_html__( 'Nenhuma sugestão de título alternativa foi gerada para este post.', 'autoblogpro' ) . '</p>';
            }
            echo '<hr style="margin:15px 0;">';

            // 4. Sugestões de Links Internos
            $internal_links = get_post_meta( $post->ID, '_autobp_internal_link_suggestions', true );
            echo '<h4>' . esc_html__( 'Sugestões de Links Internos:', 'autoblogpro' ) . '</h4>';
            if ( !empty($internal_links) && is_array($internal_links) ) {
                echo '<ul>';
                foreach ( $internal_links as $index => $link_suggestion ) {
                    $anchor_text = isset($link_suggestion['anchor_text']) ? $link_suggestion['anchor_text'] : '';
                    $url = isset($link_suggestion['url']) ? $link_suggestion['url'] : '';
                    $target_post_id = isset($link_suggestion['post_id']) ? $link_suggestion['post_id'] : 0;

                    if ( empty($anchor_text) || empty($url) ) continue;

                    $html_link = '<a href="' . esc_url( $url ) . '">' . esc_html( $anchor_text ) . '</a>';
                    ?>
                    <li>
                        Sugerido link para <a href="<?php echo esc_url( $url ); ?>" target="_blank" title="<?php echo esc_attr( $anchor_text ); ?> (ID: <?php echo esc_attr($target_post_id); ?>)"><?php echo esc_html( $anchor_text ); ?></a>
                        com o texto "<em><?php echo esc_html( $anchor_text ); ?></em>".
                        <button type="button" class="button button-small autobp-copy-internal-link-html" 
                                data-html-link="<?php echo esc_attr( $html_link ); ?>">
                            <?php esc_html_e( 'Copiar Link HTML', 'autoblogpro' ); ?>
                        </button>
                        <span class="autobp-copy-feedback" style="margin-left:10px; color:green; display:none;"><?php esc_html_e('HTML Copiado!', 'autoblogpro'); ?></span>
                    </li>
                    <?php
                }
                echo '</ul>';
            } else {
                echo '<p>' . esc_html__( 'Nenhuma sugestão de link interno foi encontrada para este post.', 'autoblogpro' ) . '</p>';
            }
            echo '<hr style="margin:15px 0;">';

            // 5. Sugestões de Links Externos
            $external_links = get_post_meta( $post->ID, '_autobp_external_link_suggestions', true );
            echo '<h4>' . esc_html__( 'Sugestões de Links Externos (Fontes):', 'autoblogpro' ) . '</h4>';
            if ( !empty($external_links) && is_array($external_links) ) {
                echo '<ul>';
                foreach ( $external_links as $ext_link_sug ) {
                     if (isset($ext_link_sug['url']) && isset($ext_link_sug['reason'])) {
                        echo '<li><a href="' . esc_url($ext_link_sug['url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($ext_link_sug['url']) . '</a>';
                        echo ' - <strong>' . esc_html__( 'Razão:', 'autoblogpro' ) . '</strong> ' . esc_html($ext_link_sug['reason']);
                        echo '</li>';
                    }
                }
                echo '</ul>';
            } else {
                echo '<p>' . esc_html__( 'Nenhuma sugestão de link externo foi gerada.', 'autoblogpro' ) . '</p>';
            }

            echo '</div>'; // .autobp-seo-suggestions-wrapper
        }
    }
}

?>
