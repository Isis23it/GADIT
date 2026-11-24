// RF-03: tras enviar un formulario con errores, el foco va al primer campo inválido.
let enviando = false;
let temporizador;

document.addEventListener(
    'submit',
    () => {
        enviando = true;
        clearTimeout(temporizador);
        // Si no aparece ningún error en 5 s, se descarta el aviso.
        temporizador = setTimeout(() => {
            enviando = false;
        }, 5000);
    },
    true,
);

new MutationObserver(() => {
    if (!enviando) return;

    const primero = document.querySelector('[aria-invalid="true"]');
    if (!primero) return;

    enviando = false;
    clearTimeout(temporizador);
    primero.focus();
}).observe(document.body, {
    subtree: true,
    childList: true,
    attributes: true,
    attributeFilter: ['aria-invalid'],
});
