const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const axios = require('axios');
const fs = require('fs');
const path = require('path');

const API_URL = 'http://127.0.0.1:8000/api/whatsapp/lead';
const BOT_NAME = 'ScamFree India WhatsApp Bot';
const LOG_FILE = path.join(__dirname, 'bot.log');
const TIMEOUT = 10000;

function log(level, message, data = null) {
    const timestamp = new Date().toISOString();
    const logEntry = `[${timestamp}] [${level}] ${message}`;

    console.log(logEntry, data || '');

    try {
        fs.appendFileSync(
            LOG_FILE,
            logEntry + (data ? ' ' + JSON.stringify(data) : '') + '\n'
        );
    } catch (e) { }
}

const client = new Client({
    authStrategy: new LocalAuth(),
    puppeteer: {
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    },
});

client.on('qr', (qr) => {
    log('INFO', 'Scan QR Code');
    qrcode.generate(qr, { small: true });
});

// READY
client.on('ready', () => {
    log('INFO', '✅ Bot Ready!');
});

client.on('message', async (msg) => {
    try {
        if (msg.from === 'status@broadcast') return;
        if (msg.from.includes('@g.us')) return;
        if (!msg.body) return;
        if (msg.fromMe) return;

        const contact = await msg.getContact();

        let phone = contact.number;

        if (!phone) {
            phone = msg.from;
        }

        phone = phone.replace(/\D/g, '');

        if (phone.length > 10 && phone.startsWith('91')) {
            phone = phone.slice(-10);
        }

        const name = contact.pushname || contact.name || 'Unknown';

        if (!phone || phone.length !== 10) {
            log('WARN', 'Invalid phone after cleaning', { phone });
            return;
        }

        log('INFO', `📩 Message from ${phone}`);

        await axios.post(API_URL, {
            phone: phone,
            message: msg.body,
            name: name
        });

        log('INFO', `✅ Lead sent: ${phone}`);

    } catch (err) {
        log('ERROR', 'Message processing error', err.message);
    }
});
process.on('unhandledRejection', (err) => {
    log('ERROR', 'Unhandled Rejection', err);
});
log('INFO', `Starting ${BOT_NAME}`);
client.initialize();