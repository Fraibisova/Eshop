document.getElementById('order_status').addEventListener('change', function() {
    const trackingGroup = document.getElementById('tracking_group');
    if (this.value === 'send') {
        trackingGroup.style.display = 'block';
    } else {
        trackingGroup.style.display = 'none';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const orderStatus = document.getElementById('order_status');
    const trackingGroup = document.getElementById('tracking_group');
    if (orderStatus.value === 'send') {
        trackingGroup.style.display = 'block';
    }
});