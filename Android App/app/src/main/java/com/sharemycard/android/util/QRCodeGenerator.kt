package com.sharemycard.android.util

import android.graphics.Bitmap
import android.graphics.Color
import com.google.zxing.BarcodeFormat
import com.google.zxing.EncodeHintType
import com.google.zxing.qrcode.QRCodeWriter
import com.sharemycard.android.domain.models.BusinessCard

object QRCodeGenerator {
    /**
     * Generates a QR code bitmap for a business card.
     * If the card has a serverCardId, generates a URL pointing to the public profile.
     * Otherwise, generates a vCard string.
     */
    fun generateQRCode(card: BusinessCard, size: Int = 512): Bitmap? {
        return try {
            val content = if (!card.serverCardId.isNullOrBlank()) {
                // Trackable QR pointing to public profile page with lead form and VCF download options
                "https://sharemycard.app/card.php?id=${card.serverCardId}&src=qr-app"
            } else {
                // Fallback: embed vCard directly (not tracked)
                createVCardString(card)
            }
            
            generateQRCodeFromString(content, size)
        } catch (e: Exception) {
            e.printStackTrace()
            null
        }
    }
    
    /**
     * Generates a QR code bitmap from a string.
     */
    fun generateQRCodeFromString(content: String, size: Int = 512): Bitmap? {
        return try {
            val hints = hashMapOf<EncodeHintType, Any>().apply {
                put(EncodeHintType.CHARACTER_SET, "UTF-8")
                put(EncodeHintType.ERROR_CORRECTION, "M") // Medium error correction
                put(EncodeHintType.MARGIN, 1)
            }
            
            val writer = QRCodeWriter()
            val bitMatrix = writer.encode(content, BarcodeFormat.QR_CODE, size, size, hints)
            
            val width = bitMatrix.width
            val height = bitMatrix.height
            val pixels = IntArray(width * height)
            
            for (y in 0 until height) {
                for (x in 0 until width) {
                    pixels[y * width + x] = if (bitMatrix[x, y]) {
                        Color.BLACK
                    } else {
                        Color.WHITE
                    }
                }
            }
            
            Bitmap.createBitmap(width, height, Bitmap.Config.RGB_565).apply {
                setPixels(pixels, 0, width, 0, 0, width, height)
            }
        } catch (e: Exception) {
            e.printStackTrace()
            null
        }
    }
    
    /**
     * Creates a vCard string from a business card.
     */
    fun createVCardString(card: BusinessCard): String {
        val vCard = StringBuilder()
        vCard.append("BEGIN:VCARD\n")
        vCard.append("VERSION:3.0\n")
        
        // Name
        vCard.append("FN:${card.fullName}\n")
        vCard.append("N:${card.lastName};${card.firstName};;;\n")
        
        // Organization
        if (!card.companyName.isNullOrBlank()) {
            vCard.append("ORG:${card.companyName}\n")
        }
        
        // Title
        if (!card.jobTitle.isNullOrBlank()) {
            vCard.append("TITLE:${card.jobTitle}\n")
        }
        
        // Phone
        if (card.phoneNumber.isNotBlank()) {
            vCard.append("TEL;TYPE=CELL:${card.phoneNumber}\n")
        }
        card.additionalPhones.forEach { phone ->
            val type = when (phone.type) {
                com.sharemycard.android.domain.models.PhoneType.WORK -> "WORK"
                com.sharemycard.android.domain.models.PhoneType.HOME -> "HOME"
                com.sharemycard.android.domain.models.PhoneType.MOBILE -> "CELL"
                com.sharemycard.android.domain.models.PhoneType.OTHER -> "VOICE"
            }
            vCard.append("TEL;TYPE=$type:${phone.phoneNumber}\n")
        }
        
        // Email
        card.primaryEmail?.let { email ->
            vCard.append("EMAIL;TYPE=INTERNET:${email.email}\n")
        }
        card.additionalEmails.forEach { email ->
            if (email != card.primaryEmail) {
                val type = when (email.type) {
                    com.sharemycard.android.domain.models.EmailType.WORK -> "WORK"
                    com.sharemycard.android.domain.models.EmailType.PERSONAL -> "HOME"
                    com.sharemycard.android.domain.models.EmailType.OTHER -> "INTERNET"
                }
                vCard.append("EMAIL;TYPE=$type:${email.email}\n")
            }
        }
        
        // Website
        card.websiteLinks.forEach { website ->
            vCard.append("URL:${website.url}\n")
        }
        
        // Address
        card.address?.let { address ->
            val addressParts = mutableListOf<String>()
            if (!address.street.isNullOrBlank()) addressParts.add(address.street!!)
            if (!address.city.isNullOrBlank()) addressParts.add(address.city!!)
            if (!address.state.isNullOrBlank()) addressParts.add(address.state!!)
            if (!address.zipCode.isNullOrBlank()) addressParts.add(address.zipCode!!)
            if (!address.country.isNullOrBlank()) addressParts.add(address.country!!)
            
            if (addressParts.isNotEmpty()) {
                vCard.append("ADR;TYPE=WORK:;;${addressParts.joinToString(";")};;;\n")
            }
        }
        
        // Bio
        if (!card.bio.isNullOrBlank()) {
            vCard.append("NOTE:${card.bio}\n")
        }
        
        vCard.append("END:VCARD")
        return vCard.toString()
    }
}

