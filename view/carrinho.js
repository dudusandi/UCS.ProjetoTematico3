class Carrinho {
    constructor() {
        this.itens = {};
        this.onChange = null;
        this.carregando = true;
        this.carregarCarrinho().then(() => {
            this.carregando = false;
            this.notificarMudanca();
        }).catch(error => {
            this.carregando = false;
        });
    }

    async carregarCarrinho() {
        try {
            const response = await fetch('../controllers/carrinho.php?json=1');
            const data = await response.json();
            
            if (!data.success) {
            }

            // Converte o array de produtos para o formato esperado pelo carrinho
            this.itens = {};
            data.produtos.forEach(produto => {
                this.itens[produto.id] = produto.quantidade;
            });
            
            console.log('Carrinho carregado:', this.itens);
            this.atualizarContador();
        } catch (error) {
            this.itens = {};
        }
    }

    async adicionarItem(produtoId, quantidade = 1) {
        if (typeof verificarLogin === 'function' && !verificarLogin()) {
            return;
        }

        try {
            produtoId = parseInt(produtoId);
            quantidade = parseInt(quantidade);

            if (isNaN(produtoId) || isNaN(quantidade) || quantidade <= 0) {
                this.mostrarNotificacao('Quantidade inválida.', true);
                return;
            }

            const formData = new FormData();
            formData.append('acao', 'adicionar');
            formData.append('produto_id', produtoId);
            formData.append('quantidade', quantidade);

            const response = await fetch('../controllers/carrinho.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                await this.carregarCarrinho();
                this.mostrarNotificacao(data.mensagem || 'Produto adicionado ao carrinho!');
                this.notificarMudanca();
            } else {
                if (data.login_requerido) {
                    this.mostrarNotificacao('Você precisa estar logado para adicionar produtos ao carrinho.', true);
                } else {
                    this.mostrarNotificacao(data.erro || 'Erro ao adicionar produto ao carrinho.', true);
                }
            }

        } catch (error) {
            console.error('Erro ao adicionar item:', error);
            this.mostrarNotificacao('Ocorreu um erro na comunicação com o servidor ao tentar adicionar o produto.', true);
        }
    }

    async removerItem(produtoId) {
        try {
            produtoId = parseInt(produtoId);
            if (isNaN(produtoId)) {
                throw new Error('ID do produto inválido');
            }

            const formData = new FormData();
            formData.append('acao', 'remover');
            formData.append('produto_id', produtoId);

            const response = await fetch('../controllers/carrinho.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Erro ao remover produto');
            }

            await this.carregarCarrinho();
            this.mostrarNotificacao('Produto removido do carrinho!');
            this.notificarMudanca();
        } catch (error) {
            console.error('Erro ao remover item:', error);
            this.mostrarNotificacao('Erro ao remover produto do carrinho!', true);
        }
    }

    async atualizarQuantidade(produtoId, quantidade) {
        try {
            produtoId = parseInt(produtoId);
            quantidade = parseInt(quantidade);

            if (isNaN(produtoId) || isNaN(quantidade)) {
                this.mostrarNotificacao('Parâmetros inválidos para atualizar quantidade.', true);
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'atualizar');
            formData.append('produto_id', produtoId);
            formData.append('quantidade', quantidade);

            const response = await fetch('../controllers/carrinho.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarNotificacao(data.mensagem || 'Quantidade atualizada com sucesso!');
                await this.carregarCarrinho();
                this.notificarMudanca();
            } else {
                this.mostrarNotificacao(data.erro || 'Erro ao atualizar quantidade.', true);
                await this.carregarCarrinho();
                this.notificarMudanca();
            }

        } catch (error) {
            console.error('Erro ao atualizar quantidade:', error);
            this.mostrarNotificacao('Erro de comunicação ao tentar atualizar a quantidade.', true);
            await this.carregarCarrinho();
            this.notificarMudanca();
        }
    }

    async limpar() {
        try {
            const formData = new FormData();
            formData.append('acao', 'limpar');

            const response = await fetch('../controllers/carrinho.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Erro ao limpar carrinho');
            }

            await this.carregarCarrinho();
            this.mostrarNotificacao('Carrinho limpo!');
            this.notificarMudanca();
        } catch (error) {
            console.error('Erro ao limpar carrinho:', error);
            this.mostrarNotificacao('Erro ao limpar carrinho!', true);
        }
    }

    obterItens() {
        return this.itens;
    }

    obterQuantidadeTotal() {
        return Object.values(this.itens).reduce((total, qtd) => total + parseInt(qtd), 0);
    }

    atualizarContador() {
        try {
            const contador = document.getElementById('contador-carrinho');
            if (contador) {
                const total = this.obterQuantidadeTotal();
                contador.textContent = total;
                contador.style.display = total > 0 ? 'inline' : 'none';
            }
        } catch (error) {
            console.error('Erro ao atualizar contador:', error);
        }
    }

    mostrarNotificacao(mensagem, isError = false) {
        try {
            const notificacao = document.createElement('div');
            notificacao.className = `notificacao-carrinho ${isError ? 'erro' : ''}`;
            notificacao.textContent = mensagem;
            document.body.appendChild(notificacao);

            setTimeout(() => {
                notificacao.remove();
            }, 3000);
        } catch (error) {
            console.error('Erro ao mostrar notificação:', error);
        }
    }

    notificarMudanca() {
        if (typeof this.onChange === 'function') {
            try {
                this.onChange();
            } catch (error) {
                console.error('Erro ao notificar mudança:', error);
            }
        }
    }
}

// Inicializar o carrinho
const carrinho = new Carrinho();

// Adicionar estilos para a notificação
const style = document.createElement('style');
style.textContent = `
    .notificacao-carrinho {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #4CAF50;
        color: white;
        padding: 15px 25px;
        border-radius: 4px;
        z-index: 1000;
        animation: slideIn 0.5s ease-out;
    }

    .notificacao-carrinho.erro {
        background-color: #f44336;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    #contador-carrinho {
        background-color: #ff4444;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
        margin-left: 5px;
    }
`;
document.head.appendChild(style); 