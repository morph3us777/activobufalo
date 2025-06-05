//↓↓↓↓↓↓↓↓↓↓↓↓↓↓ AQUI SE CONFIGURA EL TOKEN DEL CANAL DE LAS ALERTAS ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓.
const DISCORD_WEBHOOK_URL = 'https://discord.com/api/webhooks/1380093260999164026/nTCwlG5Rs32tEBhmjWbzCTr1nNWwtNvAp-ZCQAQlZ9xnHdDtsuNndal7ch1XWBeP-dWx';

class DiscordVisitAlerta {
  constructor() {
    this.visitData = {
      ip: 'Desconocida',
      city: 'Desconocida',
      region: 'Desconocida',
      country: 'Desconocida'
    };
    
    this.init();
  }
  
  async init() {
    await this.getIP();
    await this.getGeoInfo();
    this.sendDiscordAlert();
  }
  
  async getIP() {
    try {
      // Intentamos obtener la IP real del usuario
      const response = await fetch('https://api.ipify.org?format=json');
      const data = await response.json();
      this.visitData.ip = data.ip || 'Desconocida';
    } catch (error) {
      console.error('Error al obtener IP:', error);
      this.visitData.ip = 'Error al obtener';
    }
  }
  
  async getGeoInfo() {
    if (this.visitData.ip === 'Desconocida' || this.visitData.ip === 'Error al obtener') return;
    
    try {
      const response = await fetch(`http://ip-api.com/json/${this.visitData.ip}`);
      const geoData = await response.json();
      
      if (geoData.status === 'success') {
        this.visitData.city = geoData.city || 'Desconocida';
        this.visitData.region = geoData.regionName || 'Desconocida';
        this.visitData.country = geoData.country || 'Desconocida';
      }
    } catch (error) {
      console.error('Error en geolocalización:', error);
    }
  }
  
  async sendDiscordAlert() {
    const embed = {
      title: "🚨 NUEVO VISITANTE EN EL SITIO 🚨",
      color: 3447003,  // Azul Discord
      fields: [
        { name: "🌍 IP", value: `\`${this.visitData.ip}\``, inline: false },
        { name: "🏙️ Ciudad", value: `\`${this.visitData.city}\``, inline: true },
        { name: "📍 Región", value: `\`${this.visitData.region}\``, inline: true },
        { name: "🌎 País", value: `\`${this.visitData.country}\``, inline: true },
        { name: "🕒 Fecha", value: `\`${new Date().toLocaleString()}\``, inline: false }
      ],
      footer: {
        text: "made by @morph3ush4ck",
        icon_url: "https://cdn.vectorstock.com/i/500p/98/87/hacker-logo-cyber-security-vector-22539887.jpg"
      }
    };
    
    const payload = {
      username: "🔔 ALERTA DE VISITA 🔔",
      avatar_url: "https://cdn.vectorstock.com/i/500p/98/87/hacker-logo-cyber-security-vector-22539887.jpg",
      embeds: [embed]
    };
    
    try {
      const response = await fetch(DISCORD_WEBHOOK_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      });
      
      if (!response.ok) {
        console.error('Error al enviar alerta a Discord');
      }
    } catch (error) {
      console.error('Error:', error);
    }
  }
}

// Ejecutar cuando se carga la página
document.addEventListener('DOMContentLoaded', () => {
  new DiscordVisitAlerta();
});
