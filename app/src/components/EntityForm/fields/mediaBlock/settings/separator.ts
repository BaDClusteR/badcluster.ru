export default function separator() {
    const wrapper = document.createElement('div');
    wrapper.classList.add('ce-popover-item-separator');

    const line = document.createElement('div');
    line.classList.add('ce-popover-item-separator__line');

    wrapper.appendChild(line);
    return wrapper;
}
