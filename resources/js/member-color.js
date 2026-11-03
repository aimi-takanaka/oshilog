const STORAGE_KEY = 'oshilog-member-color';
const ALLOWED = ['blue', 'purple', 'pink', 'green', 'red', 'yellow', 'orange', 'white'];

function storedMemberColor() {
    const stored = localStorage.getItem(STORAGE_KEY);

    return ALLOWED.includes(stored) ? stored : 'blue';
}

function applyMemberColor(color) {
    document.documentElement.dataset.memberColor = ALLOWED.includes(color) ? color : 'blue';
}

export function setOshiLogMemberColor(color) {
    const next = ALLOWED.includes(color) ? color : 'blue';

    applyMemberColor(next);
    localStorage.setItem(STORAGE_KEY, next);
}

export function restoreMemberColor() {
    applyMemberColor(storedMemberColor());
}

window.setOshiLogMemberColor = setOshiLogMemberColor;

restoreMemberColor();

document.addEventListener('livewire:navigated', restoreMemberColor);
document.addEventListener('livewire:navigating', restoreMemberColor);

new MutationObserver(() => {
    if (document.documentElement.dataset.memberColor !== storedMemberColor()) {
        restoreMemberColor();
    }
}).observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['data-member-color'],
});
