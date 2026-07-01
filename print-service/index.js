const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const { exec } = require('child_process');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = 9100;

// Middleware
app.use(cors());
app.use(bodyParser.json({ limit: '50mb' }));
app.use(bodyParser.urlencoded({ limit: '50mb', extended: true }));

// Store print jobs
let printQueue = [];
let isPrinting = false;

// Health check
app.get('/health', (req, res) => {
    res.json({ 
        status: 'ok', 
        service: 'WARKOP Print Service',
        printer: '58printer',
        queue: printQueue.length
    });
});

// Receive print job
app.post('/print', (req, res) => {
    try {
        const { html, orderId, orderNumber } = req.body;
        
        if (!html) {
            return res.status(400).json({ success: false, message: 'HTML content required' });
        }
        
        console.log(`[${new Date().toLocaleTimeString()}] Print job received: Order ${orderNumber}`);
        
        // Add to queue
        const job = {
            id: Date.now(),
            orderId: orderId,
            orderNumber: orderNumber,
            html: html,
            timestamp: new Date()
        };
        
        printQueue.push(job);
        
        res.json({ 
            success: true, 
            message: 'Print job added to queue',
            jobId: job.id,
            queuePosition: printQueue.length
        });
        
        // Process queue
        processPrintQueue();
        
    } catch (error) {
        console.error('Error:', error);
        res.status(500).json({ success: false, message: error.message });
    }
});

// Get queue status
app.get('/queue', (req, res) => {
    res.json({
        total: printQueue.length,
        isPrinting: isPrinting,
        jobs: printQueue.map(j => ({
            id: j.id,
            orderNumber: j.orderNumber,
            timestamp: j.timestamp
        }))
    });
});

// Process print queue
function processPrintQueue() {
    if (isPrinting || printQueue.length === 0) return;
    
    isPrinting = true;
    const job = printQueue.shift();
    
    console.log(`[${new Date().toLocaleTimeString()}] Processing: ${job.orderNumber}`);
    
    // Save HTML to temp file
    const tempFile = path.join('/tmp', `receipt_${job.id}.html`);
    fs.writeFileSync(tempFile, job.html);
    
    // Convert HTML to PDF using macOS tools
    const pdfFile = path.join('/tmp', `receipt_${job.id}.pdf`);
    
    // Use Safari to print (requires AppleScript)
    const appleScript = `
        tell application "Safari"
            activate
            open "${tempFile}"
            delay 2
            tell application "System Events"
                keystroke "p" using command down
                delay 1
                keystroke tab
                delay 0.5
                keystroke "58printer" using command down
                delay 0.5
                keystroke return
                delay 2
            end tell
        end tell
    `;
    
    // Alternative: Use lp command (simpler)
    const printCmd = `lp -h localhost -P 58printer "${tempFile}" 2>&1`;
    
    exec(printCmd, (error, stdout, stderr) => {
        console.log(`[${new Date().toLocaleTimeString()}] Print result:`, error ? stderr : stdout);
        
        // Cleanup
        try {
            fs.unlinkSync(tempFile);
        } catch (e) {}
        
        isPrinting = false;
        
        // Process next job
        if (printQueue.length > 0) {
            processPrintQueue();
        }
    });
}

// Start server
app.listen(PORT, () => {
    console.log(`\n╔════════════════════════════════════════════╗`);
    console.log(`║  WARKOP Print Service                      ║`);
    console.log(`║  Port: ${PORT}                              ║`);
    console.log(`║  Printer: 58printer (Bluetooth)           ║`);
    console.log(`║  Status: Running ✓                         ║`);
    console.log(`╚════════════════════════════════════════════╝\n`);
    console.log(`API Endpoints:`);
    console.log(`  GET  http://localhost:${PORT}/health - Service status`);
    console.log(`  GET  http://localhost:${PORT}/queue   - Print queue`);
    console.log(`  POST http://localhost:${PORT}/print   - Send print job`);
    console.log(`\n`);
});
