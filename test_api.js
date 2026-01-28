import axios from 'axios';

async function testApi() {
    try {
        const response = await axios.get('http://localhost:8000/iklan-budgets/yearly-comparison', {
            params: {
                years: [2025, 2024]
            }
        });
        console.log(JSON.stringify(response.data, null, 2));
    } catch (error) {
        console.error(error.message);
        if (error.response) {
            console.error(error.response.status);
            console.error(error.response.data);
        }
    }
}

testApi();
