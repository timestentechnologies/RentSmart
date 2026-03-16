-- Insert default 2-week follow-up schedule
INSERT INTO follow_up_schedules (name, days_after_registration, subject, content, is_active) VALUES 
('2 Week Welcome Follow-up', 14, 'How are you enjoying RentSmart?', 
'<div style="max-width:600px;margin:auto;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;font-family:\'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
<div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);padding:40px;text-align:center;">
    <h1 style="color:white;margin:0;font-size:28px;">RentSmart</h1>
</div>
<div style="padding:40px;background:#ffffff;">
    <h2 style="color:#333;margin:0 0 20px 0;font-size:24px;">Hi {name},</h2>
    <p style="color:#666;font-size:16px;line-height:1.6;margin:0 0 20px 0;">It\'s been two weeks since you joined RentSmart! We hope you\'re finding the platform helpful for managing your properties.</p>
    
    <div style="background:#f8f9fa;border-left:4px solid #667eea;padding:20px;margin:30px 0;">
        <h3 style="color:#333;margin:0 0 10px 0;font-size:18px;">Quick Tips to Get Started:</h3>
        <ul style="color:#666;font-size:15px;line-height:1.6;margin:0;padding-left:20px;">
            <li>Complete your property profile with photos and detailed descriptions</li>
            <li>Set up automated rent reminders for your tenants</li>
            <li>Explore the reporting dashboard to track payments</li>
            <li>Invite team members to collaborate on property management</li>
        </ul>
    </div>
    
    <div style="text-align:center;margin:30px 0;">
        <a href="/dashboard" style="display:inline-block;background:#667eea;color:white;padding:15px 30px;text-decoration:none;border-radius:5px;font-weight:600;font-size:16px;">Visit Dashboard</a>
    </div>
    
    <p style="color:#666;font-size:14px;line-height:1.6;margin:20px 0 0 0;">If you have any questions or need help getting started, reply to this email or contact our support team. We\'re here to help you make the most of RentSmart!</p>
</div>
<div style="background:#f8f9fa;padding:20px;text-align:center;border-top:1px solid #e0e0e0;">
    <p style="color:#888;font-size:12px;margin:0 0 10px 0;">Follow us on social media for tips and updates</p>
    <p style="color:#888;font-size:11px;margin:0;">© 2026 RentSmart. All rights reserved.</p>
    <div style="margin-top:10px;font-size:12px;color:#888;text-align:center;">Powered by <a href="https://timestentechnologies.co.ke" target="_blank" style="color:#888;text-decoration:none;">Timesten Technologies</a></div>
</div>
</div>', 1);
