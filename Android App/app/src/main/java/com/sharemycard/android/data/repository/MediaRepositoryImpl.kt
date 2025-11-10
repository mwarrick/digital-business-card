package com.sharemycard.android.data.repository

import android.graphics.Bitmap
import android.util.Log
import com.sharemycard.android.data.remote.api.MediaApi
import com.sharemycard.android.data.remote.models.MediaUploadResponse
import com.sharemycard.android.data.remote.models.DeleteMediaRequest
import com.sharemycard.android.domain.repository.MediaRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.ByteArrayOutputStream
import java.io.File
import java.io.FileOutputStream
import javax.inject.Inject

class MediaRepositoryImpl @Inject constructor(
    private val mediaApi: MediaApi
) : MediaRepository {
    
    override suspend fun uploadImage(
        bitmap: Bitmap,
        cardId: String,
        mediaType: String
    ): Result<MediaUploadResponse> = withContext(Dispatchers.IO) {
        try {
            Log.d("MediaRepository", "📤 Uploading $mediaType for card $cardId...")
            
            // Compress bitmap to JPEG
            val outputStream = ByteArrayOutputStream()
            bitmap.compress(Bitmap.CompressFormat.JPEG, 80, outputStream)
            val imageBytes = outputStream.toByteArray()
            
            Log.d("MediaRepository", "  📦 Image size: ${imageBytes.size / 1024}KB")
            
            // Create temporary file
            val tempFile = File.createTempFile("upload_", ".jpg")
            FileOutputStream(tempFile).use { it.write(imageBytes) }
            
            // Create request body for card ID
            val cardIdBody = cardId.toRequestBody("text/plain".toMediaType())
            
            // Create request body for media type
            val mediaTypeBody = mediaType.toRequestBody("text/plain".toMediaType())
            
            // Create multipart file part
            val requestFile = tempFile.asRequestBody("image/jpeg".toMediaType())
            val filePart = MultipartBody.Part.createFormData("file", "image.jpg", requestFile)
            
            // Upload
            val response = mediaApi.uploadImage(cardIdBody, mediaTypeBody, filePart)
            
            // Clean up temp file
            tempFile.delete()
            
            if (response.isSuccess && response.data != null) {
                Log.d("MediaRepository", "  ✅ Upload successful: ${response.data.filename}")
                Result.success(response.data)
            } else {
                val error = "Upload failed: ${response.message}"
                Log.e("MediaRepository", error)
                Result.failure(Exception(error))
            }
        } catch (e: Exception) {
            Log.e("MediaRepository", "  ❌ Upload failed: ${e.message}", e)
            Result.failure(e)
        }
    }
    
    override suspend fun deleteImage(
        filename: String,
        cardId: String
    ): Result<Unit> = withContext(Dispatchers.IO) {
        try {
            Log.d("MediaRepository", "🗑️ Deleting image: $filename for card $cardId")
            
            val request = DeleteMediaRequest(filename, cardId)
            val response = mediaApi.deleteImage(request)
            
            if (response.isSuccess) {
                Log.d("MediaRepository", "  ✅ Delete successful")
                Result.success(Unit)
            } else {
                val error = "Delete failed: ${response.message}"
                Log.e("MediaRepository", error)
                Result.failure(Exception(error))
            }
        } catch (e: Exception) {
            Log.e("MediaRepository", "  ❌ Delete failed: ${e.message}", e)
            Result.failure(e)
        }
    }
}

