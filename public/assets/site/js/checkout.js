document.addEventListener("DOMContentLoaded", function () {
  const emailInput = document.getElementById('email');
  const blocoCadastro = document.getElementById('dados-cadastro');

  if (emailInput && blocoCadastro) {
    let ultimoEmail = "";

    function validarEmail(email) {
      return /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/.test(email);
    }

    async function verificarCliente(email) {
      if (!validarEmail(email)) {
        blocoCadastro.style.display = 'none';
        return;
      }

      try {
        const res = await fetch(`/validar-cliente?email=${encodeURIComponent(email)}`);
        const data = await res.json();

        if (data.existe) {
          blocoCadastro.style.display = 'none';
          console.log("✅ Cliente já cadastrado.");
        } else {
          blocoCadastro.style.display = 'block';
          console.log("🆕 Novo cliente. Cadastro visível.");
        }
      } catch (err) {
        console.error("Erro ao validar e-mail:", err);
      }
    }

    emailInput.addEventListener("input", () => {
      const novoEmail = emailInput.value.trim();

      if (novoEmail !== ultimoEmail) {
        ultimoEmail = novoEmail;

        if (validarEmail(novoEmail)) {
          clearTimeout(emailInput._verificaTimeout);
          emailInput._verificaTimeout = setTimeout(() => verificarCliente(novoEmail), 400);
        } else {
          blocoCadastro.style.display = 'none';
        }
      }
    });
  }

  // Busca de endereço pelo CEP
  window.buscarEndereco = function () {
    const cep = document.getElementById('cep')?.value.replace(/\D/g, '');
    if (cep.length !== 8) return;

    fetch(`https://viacep.com.br/ws/${cep}/json/`)
      .then(res => res.json())
      .then(data => {
        if (!data.erro) {
          document.getElementById('rua').value = data.logradouro || '';
          document.getElementById('bairro').value = data.bairro || '';
          document.getElementById('cidade').value = data.localidade || '';
          document.getElementById('estado').value = data.uf || '';
        }
      })
      .catch(error => console.error('Erro ao buscar CEP:', error));
  };

  // Aciona o cálculo de frete
  function tentarRegistrarFrete() {
    if (typeof Frete !== 'undefined' && typeof Frete.calcularCarrinho === 'function') {
      const calcularBtn = document.getElementById("btn-calcular-frete");
      if (calcularBtn) {
        calcularBtn.addEventListener("click", Frete.calcularCarrinho);
      }
      window.Frete.calcularCheckout = Frete.calcularCarrinho;
    } else {
      setTimeout(tentarRegistrarFrete, 300);
    }
  }

  tentarRegistrarFrete();
});