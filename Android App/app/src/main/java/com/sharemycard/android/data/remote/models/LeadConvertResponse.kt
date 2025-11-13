package com.sharemycard.android.data.remote.models

import com.google.gson.JsonDeserializationContext
import com.google.gson.JsonDeserializer
import com.google.gson.JsonElement
import com.google.gson.annotations.JsonAdapter
import com.google.gson.annotations.SerializedName
import java.lang.reflect.Type

/**
 * Response model for lead to contact conversion
 * Handles both String and Int contact_id values from server
 */
@JsonAdapter(LeadConvertResponseDeserializer::class)
data class LeadConvertResponse(
    val contactId: String
)

/**
 * Custom deserializer to handle contact_id as either String or Int
 */
class LeadConvertResponseDeserializer : JsonDeserializer<LeadConvertResponse> {
    override fun deserialize(
        json: JsonElement?,
        typeOfT: Type?,
        context: JsonDeserializationContext?
    ): LeadConvertResponse {
        if (json == null || !json.isJsonObject) {
            throw IllegalArgumentException("Invalid JSON: expected object")
        }
        
        val jsonObject = json.asJsonObject
        val contactIdElement = jsonObject.get("contact_id")
        
        if (contactIdElement == null || contactIdElement.isJsonNull) {
            throw IllegalArgumentException("contact_id is missing or null")
        }
        
        val contactId = when {
            contactIdElement.isJsonPrimitive -> {
                val primitive = contactIdElement.asJsonPrimitive
                when {
                    primitive.isString -> primitive.asString
                    primitive.isNumber -> primitive.asInt.toString()
                    else -> throw IllegalArgumentException("contact_id must be String or Int, got: ${primitive}")
                }
            }
            else -> throw IllegalArgumentException("contact_id must be a primitive value, got: ${contactIdElement.javaClass.simpleName}")
        }
        
        if (contactId.isBlank()) {
            throw IllegalArgumentException("contact_id cannot be empty")
        }
        
        return LeadConvertResponse(contactId)
    }
}

