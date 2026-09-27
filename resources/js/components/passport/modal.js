import {Modal} from 'bootstrap';

function findElement(selector) {
  return document.querySelector(selector);
}

function getModal(selector) {
  const element = findElement(selector);
  return element ? Modal.getOrCreateInstance(element) : null;
}

export function showModal(selector) {
  getModal(selector)?.show();
}

export function hideModal(selector) {
  getModal(selector)?.hide();
}

export function focusWhenModalIsShown(modalSelector, inputSelector) {
  const modal = findElement(modalSelector);
  if (!modal) {
    return () => {};
  }

  const focusInput = () => findElement(inputSelector)?.focus();
  modal.addEventListener('shown.bs.modal', focusInput);

  return () => modal.removeEventListener('shown.bs.modal', focusInput);
}
