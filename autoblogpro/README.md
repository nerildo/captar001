# AutoBlogPro

**Versão:** 0.1.0
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

## Funcionalidades Implementadas (Versão 0.1.0)

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
        *   O ID do nicho de origem é salvo como metadado (`_autobp_generated_from_niche_id`) no post gerado para rastreabilidade.
    *   **Feedback ao Usuário Aprimorado:** Mensagens claras de sucesso (com link para editar o rascunho em nova aba) e de erro (incluindo detalhes de erros da API e códigos de erro) são exibidas após a tentativa de geração, individualmente para cada artigo em um lote.
    *   **Biblioteca de Artigos:**
        *   Subpágina "AutoBlogPro > Biblioteca de Artigos" que lista todos os artigos gerados pelo plugin.
        *   Exibe colunas com Título do Artigo (com link de edição), Nicho de Origem, Data de Criação, Status e Ações.
        *   Ação "Publicar" disponível diretamente na biblioteca para artigos com status "Rascunho", permitindo publicação rápida com redirecionamento e feedback.
        *   Ações "Ver" e "Lixeira" também disponíveis.

## Próximos Passos

*   Permitir a seleção do modelo da OpenAI (GPT-3.5-turbo, GPT-4, etc.) na página de configurações.
*   Opção para publicar diretamente ou agendar posts (além de salvar como rascunho) a partir da página de geração.
*   Melhorias na geração de títulos (ex: solicitar à IA um título otimizado).
*   Refinamento contínuo da engenharia de prompts com base nos resultados.
*   Adicionar a capacidade de definir um "público-alvo" para os artigos.
*   Internacionalização completa do plugin.

---
*Este é um projeto em desenvolvimento.*
