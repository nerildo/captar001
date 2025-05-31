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
    *   Criação de um tipo de post personalizado "Nichos".
    *   Capacidade de adicionar, editar e excluir nichos.
    *   Associação de categorias do WordPress a cada nicho através de uma metabox na tela de edição do nicho.
*   **Configuração da API OpenAI:**
    *   Página de configurações dedicada ("AutoBlogPro > Configurações") para inserir e salvar a chave da API OpenAI.
*   **Geração de Artigos (Modo Automático - Estrutura Inicial):**
    *   Interface de usuário ("AutoBlogPro > Gerar Artigos") para selecionar um nicho, definir o número de artigos e palavras-chave alvo.
    *   A geração de conteúdo real ainda não está implementada; o sistema atualmente simula o processo e registra a intenção nos logs (se o debug estiver ativo).

## Próximos Passos

*   Integração real com a API da OpenAI para geração de conteúdo.
*   Criação de rascunhos de posts no WordPress com o conteúdo gerado.
*   Mais opções de personalização para a geração de artigos.

---
*Este é um projeto em desenvolvimento.*
