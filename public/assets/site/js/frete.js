window.Frete = {
  async calcular(produtoId, quantidade, cep, containerId = 'freteInfo') {
    const endpoint = `/api/frete/${produtoId}/${quantidade}/${cep}`;
    const container = document.getElementById(containerId);

    if (!cep || cep.length !== 8) {
      alert('Digite um CEP válido');
      return;
    }

    container.innerHTML = 'Calculando...';
    container.style.display = 'block';

    try {
      const response = await fetch(endpoint);
      const json = await response.json();

      if (json.status !== 'ok') {
        container.innerHTML = `<p class="frete-erro">${json.mensagem}</p>`;
        return;
      }

      const fretes = json.fretes;
      container.innerHTML = '';

      // Fallback manuais (retirada e frete local)
      const manuais = [
        { nome: 'Retirada Local', valor: 0, prazo: '1 dia útil' },
        { nome: 'Frete Local', valor: 6, prazo: '1 dia útil' }
      ];

      manuais.forEach(frete => {
        const div = document.createElement('div');
        div.className = 'frete-item';
        div.innerHTML = `${frete.nome} - R$ ${frete.valor.toFixed(2).replace('.', ',')} (${frete.prazo})`;
        div.onclick = () => Frete.selecionar(div, frete.nome, frete.valor, frete.prazo);
        container.appendChild(div);
      });

      fretes.forEach(frete => {
        if (frete.error || !frete.custom_price) return;

        const div = document.createElement('div');
        div.className = 'frete-item';
        div.innerHTML = `
          <img src="${frete.company.picture}" alt="${frete.company.name}" class="logo-frete" />
          ${frete.company.name} - ${frete.name} - R$ ${parseFloat(frete.custom_price).toFixed(2).replace('.', ',')} (${frete.custom_delivery_time} dias)
        `;
        div.onclick = () => Frete.selecionar(div, `${frete.company.name} - ${frete.name}`, parseFloat(frete.custom_price), `${frete.custom_delivery_time} dias`);
        container.appendChild(div);
      });

    } catch (err) {
      console.error(err);
      container.innerHTML = '<p class="frete-erro">Erro ao consultar frete. Tente novamente.</p>';
    }
  },

  calcularCarrinho() {
    const cepInput = document.getElementById("cep-frete");
    if (!cepInput) return;
    const cep = cepInput.value.replace(/\D/g, "");
    if (cep.length !== 8) {
      alert("Digite um CEP válido.");
      return;
    }
    // Compatível com ID do container no checkout
    Frete.calcular(0, 1, cep, 'frete-opcoes');
  },

  selecionar(div, nome, valor, prazo) {
    document.querySelectorAll('.frete-item').forEach(el => el.classList.remove('ativo'));
    div.classList.add('ativo');

    const inputHidden = document.getElementById('frete_selecionado');
    if (inputHidden) inputHidden.value = `${nome}:${valor}:${prazo}`;

    const valorFreteEl = document.getElementById('frete-valor');
    if (valorFreteEl) valorFreteEl.innerText = `R$ ${valor.toFixed(2).replace('.', ',')}`;

    const totalFinalEl = document.getElementById('total-final');
    const subtotalEl = document.getElementById('subtotalValor');
    if (totalFinalEl && subtotalEl) {
      const subtotal = parseFloat(subtotalEl.dataset.valor);
      totalFinalEl.innerText = `R$ ${(subtotal + valor).toFixed(2).replace('.', ',')}`;
    }

    // Também atualizar resumo na página de checkout, se existir
    const freteResumo = document.getElementById("freteResumo");
    const totalResumo = document.getElementById("totalResumo");
    if (freteResumo) freteResumo.innerText = `R$ ${valor.toFixed(2).replace('.', ',')} (${nome} - ${prazo})`;
    if (totalResumo && subtotalEl) {
      const subtotal = parseFloat(subtotalEl.dataset.valor);
      totalResumo.innerText = `R$ ${(subtotal + valor).toFixed(2).replace('.', ',')}`;
    }
  }
};