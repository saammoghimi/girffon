<?php
require_once __DIR__ . '/../utils/order-confirmation-mailer.php';

function girffonAdminPromotionalDefaultSubject(): string
{
    return 'GirffoN Promotional Campaign - Discover the Collection';
}

function girffonAdminPromotionalDefaultMessage(): string
{
    return 'Discover the latest GirffoN arrivals, premium seasonal picks, and refined pieces selected for customers who want the newest collection first.';
}

function girffonAdminPromotionalDefaultDiscountCode(): string
{
    return 'GIRFFON20';
}

function girffonAdminPromotionalDefaultCtaText(): string
{
    return 'Shop Collection';
}

function girffonAdminPromotionalDefaultCtaUrl(array $mailConfig = []): string
{
    if (function_exists('girffonOrderEmailBuildAppUrl')) {
        return rtrim(girffonOrderEmailBuildAppUrl($mailConfig), '/') . '/index.html';
    }

    $appUrl = trim((string) ($mailConfig['app_url'] ?? ''));
    if ($appUrl !== '') {
        return rtrim($appUrl, '/') . '/index.html';
    }

    return 'https://girffon.shop/GirffoN/index.html';
}

function girffonAdminPromotionalBuildCampaignConfig(array $input, array $mailConfig = []): array
{
    $subject = trim((string) ($input['subject'] ?? ''));
    $message = trim((string) ($input['message'] ?? ''));
    $bannerImageUrl = trim((string) ($input['banner_image_url'] ?? ''));
    $discountCode = strtoupper(trim((string) ($input['discount_code'] ?? '')));
    $ctaText = trim((string) ($input['cta_text'] ?? ''));
    $ctaUrl = trim((string) ($input['cta_url'] ?? ''));

    if ($subject === '') {
        $subject = girffonAdminPromotionalDefaultSubject();
    }

    if ($message === '') {
        $message = girffonAdminPromotionalDefaultMessage();
    }

    if ($discountCode === '') {
        $discountCode = girffonAdminPromotionalDefaultDiscountCode();
    }

    if ($ctaText === '') {
        $ctaText = girffonAdminPromotionalDefaultCtaText();
    }

    if ($ctaUrl === '') {
        $ctaUrl = girffonAdminPromotionalDefaultCtaUrl($mailConfig);
    }

    if (!filter_var($ctaUrl, FILTER_VALIDATE_URL)) {
        $ctaUrl = girffonAdminPromotionalDefaultCtaUrl($mailConfig);
    }

    if ($bannerImageUrl !== '' && !filter_var($bannerImageUrl, FILTER_VALIDATE_URL)) {
        $bannerImageUrl = '';
    }

    return [
        'subject' => $subject,
        'message' => $message,
        'banner_image_url' => $bannerImageUrl,
        'discount_code' => $discountCode,
        'cta_text' => $ctaText,
        'cta_url' => $ctaUrl,
    ];
}

function girffonAdminPromotionalBuildMessage(array $recipient, array $campaign): array
{
    $recipientName = trim((string) ($recipient['name'] ?? 'GirffoN Member'));
    if ($recipientName === '') {
        $recipientName = 'GirffoN Member';
    }

    $subject = (string) ($campaign['subject'] ?? girffonAdminPromotionalDefaultSubject());
    $messageText = (string) ($campaign['message'] ?? girffonAdminPromotionalDefaultMessage());
    $bannerImageUrl = trim((string) ($campaign['banner_image_url'] ?? ''));
    $discountCode = trim((string) ($campaign['discount_code'] ?? girffonAdminPromotionalDefaultDiscountCode()));
    $ctaText = trim((string) ($campaign['cta_text'] ?? girffonAdminPromotionalDefaultCtaText()));
    $ctaUrl = trim((string) ($campaign['cta_url'] ?? girffonAdminPromotionalDefaultCtaUrl()));

    $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
    $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($messageText, ENT_QUOTES, 'UTF-8'));
    $safeDiscountCode = htmlspecialchars($discountCode, ENT_QUOTES, 'UTF-8');
    $safeCtaText = htmlspecialchars($ctaText, ENT_QUOTES, 'UTF-8');
    $safeCtaUrl = htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8');

    $bannerBlock = '';
    if ($bannerImageUrl !== '') {
        $safeBannerImageUrl = htmlspecialchars($bannerImageUrl, ENT_QUOTES, 'UTF-8');
        $bannerBlock = '<tr><td style="padding:0 34px 0;background:linear-gradient(180deg,#17181c 0%,#141416 100%);">'
            . '<img src="' . $safeBannerImageUrl . '" alt="GirffoN campaign banner" style="display:block;width:100%;height:auto;border:1px solid #3a2d14;object-fit:cover;">'
            . '</td></tr>';
    }

    $html = '<!DOCTYPE html><html lang="en"><body style="margin:0;padding:24px;background:#0f0f10;font-family:Georgia,Times New Roman,serif;color:#f5ecdc;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:760px;margin:0 auto;background:#17181c;border:1px solid #3a2d14;box-shadow:0 18px 48px rgba(0,0,0,0.32);">'
        . '<tr><td style="padding:30px 34px;background:#111215;border-bottom:1px solid #3a2d14;color:#f4ebdf;">'
        . '<div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#d1a12b;">GirffoN</div>'
        . '<h1 style="margin:12px 0 0;font-size:30px;line-height:1.2;color:#f7efe2;">' . $safeSubject . '</h1>'
        . '</td></tr>'
        . $bannerBlock
        . '<tr><td style="padding:34px;background:linear-gradient(180deg,#17181c 0%,#141416 100%);">'
        . '<p style="margin:0 0 16px;font-size:15px;line-height:1.8;color:#f4ebdf;">Hello ' . $safeName . ',</p>'
        . '<p style="margin:0 0 18px;font-size:15px;line-height:1.9;color:#d8ccb8;">' . $safeMessage . '</p>'
        . '<div style="margin:0 0 24px;padding:18px 20px;border:1px solid #8e6b2f;background:linear-gradient(135deg,#1a1713 0%,#251d10 100%);">'
        . '<div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#b89655;margin-bottom:8px;">Exclusive Discount Code</div>'
        . '<div style="font-size:28px;font-weight:700;letter-spacing:1px;color:#f1d59a;">' . $safeDiscountCode . '</div>'
        . '<div style="margin-top:8px;font-size:13px;line-height:1.7;color:#cbb28a;">Use this premium campaign code at checkout to unlock your GirffoN offer.</div>'
        . '</div>'
        . '<p style="margin:0 0 26px;font-size:15px;line-height:1.9;color:#d8ccb8;">Explore the current GirffoN collection and shop the latest premium pieces selected for this campaign.</p>'
        . '<p style="margin:0 0 28px;"><a href="' . $safeCtaUrl . '" style="display:inline-block;padding:14px 24px;background:#c9a56a;color:#17181c;text-decoration:none;font-weight:700;letter-spacing:0.4px;">' . $safeCtaText . '</a></p>'
        . '<p style="margin:0;color:#8f7c61;font-size:13px;line-height:1.8;">You are receiving this promotional update because promotional emails are enabled for your GirffoN account or subscription.</p>'
        . '</td></tr>'
        . '<tr><td style="padding:18px 34px;border-top:1px solid #3a2d14;background:#111215;color:#8f7c61;font-size:12px;line-height:1.7;">GirffoN Admin Campaign System | Premium fashion updates from GirffoN.</td></tr>'
        . '</table></body></html>';

    $text = $subject . "\n\n"
        . "Hello {$recipientName},\n\n"
        . $messageText . "\n\n"
        . "Discount Code: {$discountCode}\n"
        . $ctaText . ': ' . $ctaUrl . "\n\n"
        . "You are receiving this promotional update because promotional emails are enabled for your GirffoN account or subscription.\n";

    return [
        'to_email' => (string) ($recipient['email'] ?? ''),
        'to_name' => $recipientName,
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
        'preview_html' => $html,
    ];
}