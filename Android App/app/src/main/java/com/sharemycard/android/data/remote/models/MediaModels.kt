package com.sharemycard.android.data.remote.models

import com.google.gson.annotations.SerializedName

data class MediaUploadResponse(
    val filename: String,
    val url: String? = null,
    val path: String? = null,
    @SerializedName("media_type")
    val mediaType: String? = null,
    @SerializedName("business_card_id")
    val businessCardId: String? = null,
    val size: Long? = null,
    @SerializedName("mime_type")
    val mimeType: String? = null
)

data class DeleteMediaRequest(
    val filename: String,
    @SerializedName("business_card_id")
    val businessCardId: String
)

