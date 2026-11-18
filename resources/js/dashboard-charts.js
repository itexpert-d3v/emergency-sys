import { createApp } from 'vue';
import TrendChart from './components/TrendChart.vue';
import StatusChart from './components/StatusChart.vue';

const mount = (selector, component) => {
    document.querySelectorAll(selector).forEach((el) => {
        const data = JSON.parse(el.dataset.points || '[]');
        createApp(component, { data }).mount(el);
    });
};

mount('[data-chart="trend"]', TrendChart);
mount('[data-chart="status"]', StatusChart);
