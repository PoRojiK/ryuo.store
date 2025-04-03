function showNotification(message, color) {
    console.log('Показываем уведомление:', message, color); // Логирование

    // Проверяем, существует ли уже уведомление
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Создаём новое уведомление
    const notification = document.createElement('div');
    notification.className = 'notification fixed top-4 right-4 text-white py-2 px-4 rounded shadow-md z-50';
    
    // Устанавливаем цвет фона напрямую через стили вместо Tailwind классов
    if (color === 'green') {
        notification.style.backgroundColor = '#10B981'; // зеленый цвет
    } else if (color === 'red') {
        notification.style.backgroundColor = '#EF4444'; // красный цвет
    } else {
        notification.style.backgroundColor = '#3B82F6'; // синий цвет по умолчанию
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Удаляем уведомление через 3 секунды
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
            notification.remove();
        }, 500);
    }, 3000);
}