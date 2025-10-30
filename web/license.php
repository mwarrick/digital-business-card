<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Agreement</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            color: #222;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .header { text-align: center; margin-bottom: 40px; padding-bottom: 30px; border-bottom: 2px solid #f0f0f0; }
        .header .logo { font-size: 48px; margin-bottom: 10px; }
        h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        .last-updated { color: #666; font-size: 14px; font-style: italic; }
        .content {
            line-height: 1.45; /* tighten line spacing */
            font-size: 16px;
            color: #333;
            white-space: normal; /* render HTML paragraphs/lists normally */
        }
        .content p { margin: 8px 0; }
        .content h2 { margin: 4px 0 10px; }
        .content h3 { margin: 14px 0 6px; font-size: 18px; }
        .content ul, .content ol { margin: 6px 0 6px 20px; }
        .content li { margin: 2px 0; }
        a.back-link { display: inline-block; margin-bottom: 20px; color: #667eea; text-decoration: none; font-weight: 500; transition: transform 0.2s; }
        a.back-link:hover { transform: translateX(-5px); }
        @media (max-width: 768px) { .container { padding: 30px 20px; } h1 { font-size: 24px; } .content h3 { font-size: 17px; } }
    </style>
    <meta name="robots" content="noindex">
</head>
<body>
    <div class="container">
        <a href="/" class="back-link">← Back to Home</a>
        <div class="header">
            <div class="logo">📄</div>
            <h1>License Agreement</h1>
            <p class="last-updated"><strong>Effective date:</strong> <?php echo date('F j, Y'); ?></p>
        </div>
        <div class="content">
            

            <h3>Agreement to Terms</h3>
            <p>By accessing or using the ShareMyCard.app mobile application and web service (collectively, 
            the "Service"), you agree to be bound by this License Agreement. If you do not agree to these 
            terms, do not use the Service.</p>

            <h3>Grant of License</h3>
            <p>ShareMyCard.app grants you a free, non-exclusive, non-transferable, revocable license to use the 
            Service for personal and commercial purposes, subject to the terms and conditions set forth in 
            this Agreement.</p>

            <h3>Free Service</h3>
            <p>The Service is provided to you free of charge. We reserve the right to modify, suspend, or 
            discontinue the Service (or any part thereof) at any time, with or without notice, for any reason.</p>

            <h3>No Warranty</h3>
            <p>THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT 
            WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED.</p>
            <p>To the fullest extent permitted by applicable law, ShareMyCard.app disclaims all warranties, 
            including but not limited to:</p>
            <ul>
                <li>Implied warranties of merchantability, fitness for a particular purpose, and non-
                infringement</li>
                <li>Any warranties regarding the accuracy, reliability, or completeness of the Service</li>
                <li>Any warranties that the Service will be uninterrupted, secure, or error-free</li>
                <li>Any warranties regarding the results obtained from using the Service</li>
                <li>Any warranties concerning the security of data transmission or storage</li>
            </ul>
            <p>You acknowledge and agree that:</p>
            <ul>
                <li>Your use of the Service is at your sole risk</li>
                <li>We do not warrant that the Service will meet your requirements</li>
                <li>We do not warrant that any defects in the Service will be corrected</li>
                <li>Any material downloaded or obtained through the Service is done at your own discretion 
                and risk</li>
            </ul>

            <h3>Limitation of Liability</h3>
            <p>TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, 
            SHAREMYCARD.APP SHALL NOT BE LIABLE FOR ANY INDIRECT, 
            INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR ANY 
            LOSS OF PROFITS OR REVENUES, WHETHER INCURRED DIRECTLY OR 
            INDIRECTLY, OR ANY LOSS OF DATA, USE, GOODWILL, OR OTHER 
            INTANGIBLE LOSSES.</p>
            <p>This limitation applies to damages resulting from:</p>
            <ul>
                <li>Your use or inability to use the Service</li>
                <li>Any unauthorized access to or alteration of your data</li>
                <li>Any errors, omissions, or inaccuracies in the Service</li>
                <li>Any interruption or cessation of the Service</li>
                <li>Any bugs, viruses, or malicious code transmitted through the Service</li>
                <li>Any loss or damage to your business cards, data, or other content</li>
            </ul>
            <p>In no event shall ShareMyCard.app's total liability to you exceed the amount you paid to 
            use the Service in the twelve (12) months preceding the claim (which is zero dollars for free 
            users).</p>

            <h3>User Responsibilities</h3>
            <p>You are solely responsible for:</p>
            <ul>
                <li>The accuracy and legality of the business card information you provide</li>
                <li>Compliance with all applicable laws when using the Service</li>
                <li>Maintaining the confidentiality of your account access</li>
                <li>Any activity that occurs through your account</li>
                <li>Backing up any data you consider important</li>
            </ul>

            <h3>Acceptable Use</h3>
            <p>You agree NOT to:</p>
            <ul>
                <li>Use the Service for any unlawful purpose</li>
                <li>Upload or share content that infringes intellectual property rights</li>
                <li>Use the Service to transmit spam, harassment, or malicious content</li>
                <li>Attempt to interfere with, compromise, or disrupt the Service</li>
                <li>Reverse engineer or attempt to extract source code from the Service (except as permitted 
                by the open source license for the code itself)</li>
                <li>Use automated tools to access the Service without permission</li>
            </ul>

            <h3>Open Source Software</h3>
            <p>The source code for ShareMyCard.app is available under an open source license at 
            https://github.com/mwarrick/digital-business-card. The open source license governs your use of 
            the source code itself. This License Agreement governs your use of the hosted Service.</p>

            <h3>Intellectual Property</h3>
            <p>All content, features, and functionality of the Service (excluding user-generated content and open 
            source code) are owned by ShareMyCard.app and are protected by copyright, trademark, and 
            other intellectual property laws.</p>

            <h3>Data and Privacy</h3>
            <p>Your use of the Service is also governed by our Privacy Policy, which is incorporated into this 
            Agreement by reference. By using the Service, you consent to the collection and use of your 
            information as described in the Privacy Policy.</p>

            <h3>Termination</h3>
            <p>We reserve the right to terminate or suspend your access to the Service immediately, without 
            prior notice or liability, for any reason, including:</p>
            <ul>
                <li>Breach of this Agreement</li>
                <li>Request by law enforcement or government agencies</li>
                <li>Technical or security issues</li>
                <li>Discontinuation of the Service</li>
            </ul>
            <p>Upon termination:</p>
            <ul>
                <li>Your right to use the Service will immediately cease</li>
                <li>You may export your data as described in the Privacy Policy</li>
                <li>Provisions that by their nature should survive termination shall survive</li>
            </ul>

            <h3>Indemnification</h3>
            <p>You agree to indemnify, defend, and hold harmless ShareMyCard.app, its officers, directors, 
            employees, and agents from any claims, liabilities, damages, losses, costs, or expenses (including 
            reasonable attorneys' fees) arising from:</p>
            <ul>
                <li>Your use or misuse of the Service</li>
                <li>Your violation of this Agreement</li>
                <li>Your violation of any rights of another party</li>
                <li>Any content you submit through the Service</li>
            </ul>

            <h3>Changes to This Agreement</h3>
            <p>We reserve the right to modify this Agreement at any time. We will notify you of material 
            changes by:</p>
            <ul>
                <li>Updating the "Last Updated" date at the top of this Agreement</li>
                <li>Sending an email notification</li>
                <li>Displaying a notice in the Service</li>
            </ul>
            <p>Your continued use of the Service after changes constitutes acceptance of the modified 
            Agreement.</p>

            <h3>Governing Law</h3>
            <p>This Agreement shall be governed by and construed in accordance with the laws of California, 
            USA without regard to its conflict of law provisions.</p>

            <h3>Dispute Resolution</h3>
            <p>Any disputes arising from this Agreement or your use of the Service shall be resolved through:</p>
            <ol>
                <li>Good faith negotiation between the parties</li>
                <li>If negotiation fails, binding arbitration in Orange County, California, USA</li>
            </ol>

            <h3>Severability</h3>
            <p>If any provision of this Agreement is found to be unenforceable or invalid, that provision shall be 
            limited or eliminated to the minimum extent necessary so that this Agreement shall otherwise 
            remain in full force and effect.</p>

            <h3>Entire Agreement</h3>
            <p>This Agreement, together with the Privacy Policy, constitutes the entire agreement between you 
            and ShareMyCard.app regarding the Service and supersedes all prior agreements and 
            understandings.</p>

            <h3>Contact Information</h3>
            <p>If you have questions about this Agreement, please contact us at:<br>
            Email: mark@warrick.net <br>
            Website: https://ShareMyCard.app.app</p>

            <h3>Acknowledgment</h3>
            <p>BY USING THE SERVICE, YOU ACKNOWLEDGE THAT YOU HAVE READ THIS 
            AGREEMENT, UNDERSTAND IT, AND AGREE TO BE BOUND BY ITS TERMS AND 
            CONDITIONS.</p>

            <p>This is a legally binding agreement. Please read it carefully before using ShareMyCard.app.</p>
        </div>
    </div>
</body>
</html>

