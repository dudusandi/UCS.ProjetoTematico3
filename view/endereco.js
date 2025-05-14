/**
 * Funções para manipulação de endereço
 * - Busca de CEP via ViaCEP
 * - Carregamento de cidades via IBGE
 */

function inicializarEndereco() {
    // Carrega os estados ao carregar a página
    $.getJSON('https://servicodados.ibge.gov.br/api/v1/localidades/estados', function(data) {
        var items = [];
        items.push('<option value="">Selecione um estado</option>');
        $.each(data, function(key, val) {
            var selected = val.sigla === $('#estado').data('selected') ? 'selected' : '';
            items.push('<option value="' + val.sigla + '" ' + selected + '>' + val.nome + '</option>');
        });
        $('#estado').html(items.join(''));
        
        // Se já tiver um estado selecionado, carrega as cidades
        var estadoSelecionado = $('#estado').data('selected');
        if (estadoSelecionado) {
            carregarCidades(estadoSelecionado, $('#cidade').data('selected'));
        }
    });

    // Quando o estado é selecionado, carrega as cidades
    $('#estado').change(function() {
        var uf = $(this).val();
        if (uf) {
            $('#cidade').prop('disabled', false);
            carregarCidades(uf);
        } else {
            $('#cidade').prop('disabled', true).html('<option value="">Selecione um estado primeiro</option>');
        }
    });

    // Autocompletar endereço via CEP usando ViaCEP
    $('#cep').blur(function() {
        var cep = $(this).val().replace(/\D/g, '');
        if (cep.length === 8) {
            $.getJSON('http://viacep.com.br/ws/' + cep + '/json/', function(data) {
                if (!data.erro) {
                    $('#rua').val(data.logradouro);
                    $('#bairro').val(data.bairro);
                    $('#complemento').val(data.complemento);
                    
                    // Primeiro atualiza o estado
                    $('#estado').val(data.uf);
                    
                    // Depois carrega as cidades do estado
                    carregarCidades(data.uf, data.localidade);
                } else {
                    alert('CEP não encontrado');
                }
            }).fail(function() {
                alert('Erro ao consultar CEP');
            });
        }
    });
}

// Função para carregar cidades
function carregarCidades(uf, cidadeSelecionada = null) {
    $('#cidade').prop('disabled', false);
    $.getJSON('https://servicodados.ibge.gov.br/api/v1/localidades/estados/' + uf + '/municipios', function(data) {
        var items = [];
        items.push('<option value="">Selecione uma cidade</option>');
        $.each(data, function(key, val) {
            var selected = cidadeSelecionada && val.nome === cidadeSelecionada ? 'selected' : '';
            items.push('<option value="' + val.nome + '" ' + selected + '>' + val.nome + '</option>');
        });
        $('#cidade').html(items.join(''));
    });
}

// Inicializa quando o documento estiver pronto
$(document).ready(function() {
    inicializarEndereco();
}); 