package com.sharemycard.android.domain.repository

import android.graphics.Bitmap
import com.sharemycard.android.data.remote.models.MediaUploadResponse

interface MediaRepository {
    suspend fun uploadImage(
        bitmap: Bitmap,
        cardId: String,
        mediaType: String
    ): Result<MediaUploadResponse>
    
    suspend fun deleteImage(
        filename: String,
        cardId: String
    ): Result<Unit>
}

