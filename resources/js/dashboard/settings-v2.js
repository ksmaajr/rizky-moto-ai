/*
 * Settings UI V2
 * Visual interaction only.
 * The Test Connection button intentionally does not fake an OpenAI response.
 * Connect it to a Livewire backend action when the API integration is ready.
 */

document.addEventListener('click', (event) => {
    const testButton = event.target.closest('.rms-settings-v2-test');

    if (!testButton) return;

    if (testButton.dataset.busy === '1') return;

    testButton.dataset.busy = '1';
    testButton.classList.add('is-testing');

    const label = testButton.querySelector('.rms-test-arrow');
    const text = testButton.querySelector('.rms-test-spinner')?.nextElementSibling;

    if (text) text.textContent = 'Testing connection...';

    /*
     * Backend integration will replace this visual state.
     * No fake "Connected" result is generated here.
     */
});
