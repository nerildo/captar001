# AutoBlogPro

**Versão:** 0.2.0 
**Autor:** Seu Nome/Empresa Aqui ([https://example.com/](https://example.com/))
**Licença:** GPL v2 or later

Plugin para WordPress que visa automatizar a geração de artigos de blog utilizando Inteligência Artificial.

## Descrição

AutoBlogPro permite aos usuários definir nichos, associar categorias e gerar artigos automaticamente (atualmente em modo de simulação) com base em palavras-chave e configurações da API OpenAI.

## Instalação

1.  Baixe o arquivo `.zip` do plugin (ou clone o repositório).
2.  No painel de administração do WordPress, vá para `Plugins > Adicionar Novo`.
3.  Clique em `Enviar plugin` no topo da página.
4.  Escolha o arquivo `.zip` que você baixou e clique em `Instalar agora`.
5.  Ative o plugin através do menu 'Plugins' no WordPress.

## Funcionalidades Implementadas (Versão 0.2.0)

*   **Gerenciamento de Nichos:**
    *   Criação de um tipo de post personalizado "Nichos" (CPT `niche`).
    *   Capacidade de adicionar, editar e excluir nichos.
    *   Associação de múltiplas categorias do WordPress a cada nicho através de uma metabox dedicada na tela de edição do nicho.
*   **Configuração da API OpenAI:**
    *   Página de configurações ("AutoBlogPro > Configurações") para inserir e salvar de forma segura a chave da API OpenAI.
*   **Geração de Artigos:**
    *   **Integração com API OpenAI:** Utiliza o modelo `gpt-3.5-turbo` (ou configurável futuramente) para gerar texto para os artigos.
    *   **Formulário de Geração Expandido:** Interface em "AutoBlogPro > Gerar Artigos" permite ao usuário especificar:
        *   Nicho de origem (para herdar categorias).
        *   Número de artigos a gerar no lote.
        *   Palavras-chave alvo para o conteúdo.
        *   Tamanho Médio Estimado do artigo (em palavras).
        *   Tom de Voz (ex: Formal, Informal, Criativo).
        *   Estilo de Escrita (ex: Informativo, Narrativo, Tutorial).
        *   Nível de Criatividade (controla o parâmetro `temperature` da API).
        *   Palavras-chave Negativas (termos a serem evitados pela IA).
        *   Idioma de geração do conteúdo.
        *   Número de Seções Principais (H2) desejadas para estruturar o artigo.
    *   **Construção Dinâmica de Prompts:** Os parâmetros do formulário são usados para construir prompts detalhados (de sistema e de usuário) para a API OpenAI, visando um melhor controle sobre o resultado da geração.
    *   **Geração de Múltiplos Artigos:** Suporte à geração de vários artigos em um único lote, com feedback individualizado para cada tentativa e diferenciação nos títulos dos posts gerados (ex: "Título (#1/3)").
    *   **Criação de Posts (Rascunhos):** O conteúdo gerado pela API é automaticamente salvo como um novo post do WordPress com status "rascunho".
    *   **Atribuição de Dados ao Post:**
        *   O título do post é gerado com base nas palavras-chave e, se em lote, inclui um contador.
        *   As categorias associadas ao nicho selecionado são automaticamente atribuídas ao novo post.
        *   O ID do nicho de origem e todos os parâmetros de geração são salvos como metadados no post gerado para rastreabilidade e futura regeneração.
    *   **Feedback ao Usuário Aprimorado:** Mensagens claras de sucesso (com link para editar o rascunho em nova aba) e de erro (incluindo detalhes de erros da API e códigos de erro) são exibidas após a tentativa de geração, individualmente para cada artigo em um lote.
    *   **Modo URL Base para Geração de Artigos:**
        *   Permite ao usuário submeter uma URL de um artigo existente como ponto de partida.
        *   Extrai o conteúdo textual principal da página da URL fornecida.
        *   Realiza análise semântica no texto extraído (identificando palavras-chave e tópicos principais) através da API OpenAI.
        *   Busca insights adicionais e fatos relevantes sobre os tópicos identificados, utilizando a API OpenAI para simular uma pesquisa aprofundada.
        *   Capacidade de reescrever e expandir o conteúdo original, incorporando a análise e os insights, para gerar um novo artigo otimizado (salvo como rascunho), com parâmetros de geração também salvos como metadados.
    *   **Biblioteca de Artigos:**
        *   Subpágina "AutoBlogPro > Biblioteca de Artigos" que lista todos os artigos gerados pelo plugin.
        *   **Paginação:** Implementada para facilitar a navegação por um grande número de artigos.
        *   **Filtros:** Adicionados filtros por Nicho de Origem e Status do Post.
        *   **Detalhes Exibidos:** Colunas para Título do Artigo (com link de edição), Nicho de Origem, Data de Criação, Status e Ações.
        *   **Ações Rápidas:**
            *   "Publicar": Para rascunhos, com redirecionamento e feedback.
            *   "Agendar": Para rascunhos, com interface para selecionar data/hora futura e feedback.
            *   "Mover para Lixeira": Com confirmação JavaScript e feedback.
            *   "Regenerar Artigo": Permite criar uma nova versão de um artigo gerado anteriormente, utilizando os mesmos parâmetros de entrada originais (para ambos os modos de geração: automático e URL base).
            *   Links "Ver" e "Editar" padrão.
    *   **Verificação de Plágio (Copyscape):**
        *   Seção de Configuração para Nome de Usuário e Chave de API Copyscape.
        *   Botão "Verificar Plágio" na Biblioteca de Artigos para cada post.
        *   Verificação de plágio via AJAX, utilizando a API Copyscape.
        *   Exibição dos resultados (contagem de cópias, link para relatório Copyscape se houver resultados, custo da verificação) e salvamento dos resultados como metadados do post.
    *   **Biblioteca de Artigos - Funcionalidade da Lixeira Completa:**
        *   Links de filtro de status para visualizar posts na lixeira (`post_status='trash'`).
        *   Ação 'Restaurar' para itens na lixeira, que os retorna ao status 'draft'.
        *   Ação 'Excluir Permanentemente' para itens na lixeira, com diálogo de confirmação.
*   **Otimização SEO:**
    *   Geração automática de meta descrição (via API OpenAI) durante a criação de artigos (para ambos os modos), salva como metadado do post (`_autobp_meta_description`).
    *   Sistema básico de sugestão de links internos que analisa o conteúdo do artigo gerado em busca de títulos de outros posts publicados; sugestões salvas como metadados (`_autobp_internal_link_suggestions`).
    *   Geração/Sugestão de até 5 alternativas de títulos otimizados para SEO (via API OpenAI) após a criação do artigo, salvos como metadado do post (`_autobp_title_suggestions`).
    *   Implementação automática de Schema Markup (JSON-LD do tipo `Article`) para todos os posts gerados pelo plugin, injetado no `wp_head` de páginas de post singular.
    *   Análise da densidade da palavra-chave foco principal no conteúdo gerado, com o resultado (palavra-chave e percentual de densidade) salvo como metadado do post (`_autobp_focus_keyword`, `_autobp_keyword_density`).
    *   Sugestão de 2-3 links externos relevantes e de autoridade (com URL e justificativa, via API OpenAI), salvos como metadado do post (`_autobp_external_link_suggestions`) para enriquecimento do conteúdo.
    *   Geração/Refinamento de texto alternativo (alt text) para imagens destacadas (especialmente aquelas originadas da Pexels) utilizando a API OpenAI. O alt text é otimizado com base no título do post e/ou palavras-chave foco, com fallback para o alt text original da Pexels se a otimização falhar ou a API OpenAI não estiver configurada.
*   **Interface de SEO Otimizada:**
    *   Adição de uma metabox customizada na tela de edição dos posts gerados ("Sugestões de Otimização SEO"). Esta metabox exibe:
        *   Meta descrição sugerida (com botão para copiar).
        *   Palavra-chave foco e sua densidade.
        *   Sugestões de títulos alternativos.
        *   Sugestões de links internos.
        *   Sugestões de links externos.
*   **Integração com Plugins de SEO Populares:**
    *   Opções na página de Configurações para selecionar Yoast SEO ou Rank Math como o plugin de SEO ativo.
    *   Se habilitado, o AutoBlogPro preencherá automaticamente os campos de palavra-chave foco e meta descrição desses plugins com os dados gerados.
*   **Integração com Pexels API para Imagens:**
    *   Permite configurar a Chave de API Pexels na página de Configurações.
    *   Na Biblioteca de Artigos, usuários podem buscar imagens na Pexels com base no título do post via AJAX.
    *   Exibição de miniaturas das imagens encontradas com opção para o usuário selecionar uma.
    *   Funcionalidade para definir a imagem selecionada como imagem destacada do post (a imagem é baixada para a biblioteca de mídia do WordPress e associada ao post).
*   **Sistema de Logs Detalhado:**
    *   Criação de uma tabela dedicada (`wp_autoblogpro_logs`) no banco de dados para logs.
    *   A classe `AutoBP_Log_Manager` permite registrar eventos importantes, sucessos, falhas e informações de depuração em diferentes níveis (INFO, ERROR, WARNING, DEBUG).
    *   Interface de administração em "AutoBlogPro > Logs do Sistema" para visualizar os logs com paginação e opção para limpar todos os logs.
    *   Cobertura de logging expandida e completada nos principais processos do plugin, incluindo os fluxos detalhados do Modo URL Base (fetch, extract, analyze, insights, rewrite) e a funcionalidade de regeneração de artigos. Isso garante um rastreamento robusto para depuração e monitoramento.
*   **Interface de Administração (UI/UX):**
    *   A página "Gerar Artigos" foi refatorada para utilizar abas, separando de forma organizada o "Modo Automático" e o "Modo URL Base", melhorando a usabilidade.
*   **Qualidade e Testes:**
    *   Início da implementação de testes unitários com PHPUnit para garantir a estabilidade e corretude do código. Testes iniciais foram criados para a classe `AutoBP_Log_Manager`.
    *   Configuração básica do ambiente de testes PHPUnit (arquivos `phpunit.xml.dist` e `tests/bootstrap.php`) estabelecida.

## Próximos Passos

*   Permitir a seleção do modelo da OpenAI (GPT-3.5-turbo, GPT-4, etc.) na página de configurações.
*   Opção para publicar diretamente ou agendar posts (além de salvar como rascunho) a partir da página de geração.
*   Refinamento contínuo da engenharia de prompts com base nos resultados.
*   Adicionar a capacidade de definir um "público-alvo" para os artigos.
*   Internacionalização completa do plugin.
*   Melhorar a interface do usuário para visualização e gerenciamento de sugestões de SEO (títulos, links internos/externos, etc.) diretamente na tela de edição de posts.
*   Integrar com outras fontes de imagens além da Pexels.
*   Adicionar suporte para mais plugins de SEO.
*   Expandir a cobertura de testes unitários e de integração.
---
*Este é um projeto em desenvolvimento.*

