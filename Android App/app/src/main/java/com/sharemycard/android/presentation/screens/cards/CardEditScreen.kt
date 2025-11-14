package com.sharemycard.android.presentation.screens.cards

import android.content.Intent
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.core.content.FileProvider
import com.yalantis.ucrop.UCrop
import java.io.File
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import coil.compose.AsyncImage
import com.sharemycard.android.domain.models.*
import com.sharemycard.android.presentation.viewmodel.CardEditViewModel
import com.sharemycard.android.util.CardThemes
import java.util.UUID

enum class ImageType {
    PROFILE_PHOTO,
    COMPANY_LOGO,
    COVER_GRAPHIC
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CardEditScreen(
    cardId: String?,
    onNavigateBack: () -> Unit = {},
    viewModel: CardEditViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    val context = LocalContext.current
    
    LaunchedEffect(cardId) {
        viewModel.initialize(cardId)
    }
    
    LaunchedEffect(uiState.shouldNavigateBack) {
        if (uiState.shouldNavigateBack) {
            onNavigateBack()
        }
    }
    
    // Store which image type we're cropping
    var croppingImageType by remember { mutableStateOf<ImageType?>(null) }
    
    // Crop image launcher
    val cropImageLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.StartActivityForResult()
    ) { result ->
        val imageType = croppingImageType
        croppingImageType = null
        
        if (result.resultCode == android.app.Activity.RESULT_OK) {
            val resultData = result.data
            if (resultData != null) {
                val resultUri = UCrop.getOutput(resultData)
                resultUri?.let { uri ->
                    context.contentResolver.openInputStream(uri)?.use { inputStream ->
                        val bitmap = BitmapFactory.decodeStream(inputStream)
                        bitmap?.let {
                            when (imageType) {
                                ImageType.PROFILE_PHOTO -> viewModel.setProfilePhoto(it)
                                ImageType.COMPANY_LOGO -> viewModel.setCompanyLogo(it)
                                ImageType.COVER_GRAPHIC -> viewModel.setCoverGraphic(it)
                                null -> { /* No action */ }
                            }
                        }
                    }
                }
            }
        } else if (result.resultCode == UCrop.RESULT_ERROR) {
            val resultData = result.data
            if (resultData != null) {
                val cropError = UCrop.getError(resultData)
                android.util.Log.e("CardEditScreen", "Crop error: ${cropError?.message}")
            }
        }
    }
    
    // Helper function to create a temporary file for cropped image
    fun createTempImageFile(): File {
        val cacheDir = context.cacheDir
        return File.createTempFile("cropped_${System.currentTimeMillis()}", ".jpg", cacheDir)
    }
    
    // Helper function to get FileProvider URI
    fun getFileProviderUri(file: File): Uri {
        return FileProvider.getUriForFile(
            context,
            "${context.packageName}.fileprovider",
            file
        )
    }
    
    // Helper function to start cropping
    fun startCrop(sourceUri: Uri, imageType: ImageType) {
        val destinationFile = createTempImageFile()
        val destinationUri = getFileProviderUri(destinationFile)
        
        croppingImageType = imageType
        
        // Build UCrop with aspect ratio based on image type
        val uCropBuilder = UCrop.of(sourceUri, destinationUri)
            .withMaxResultSize(2000, 2000)
        
        // Set aspect ratio based on image type
        val uCrop = when (imageType) {
            ImageType.PROFILE_PHOTO -> uCropBuilder.withAspectRatio(1f, 1f) // Square for profile
            ImageType.COMPANY_LOGO -> uCropBuilder.withAspectRatio(4f, 3f) // 4:3 for logo
            ImageType.COVER_GRAPHIC -> uCropBuilder.withAspectRatio(16f, 9f) // 16:9 for cover
        }
        
        val intent = uCrop.getIntent(context)
        if (intent != null) {
            cropImageLauncher.launch(intent)
        } else {
            android.util.Log.e("CardEditScreen", "Failed to create crop intent")
        }
    }
    
    // Image picker launchers for profile photo
    val profilePhotoGalleryLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetContent()
    ) { uri: Uri? ->
        uri?.let {
            startCrop(it, ImageType.PROFILE_PHOTO)
        }
    }
    
    // Image picker launchers for company logo
    val logoGalleryLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetContent()
    ) { uri: Uri? ->
        uri?.let {
            startCrop(it, ImageType.COMPANY_LOGO)
        }
    }
    
    // Image picker launchers for cover graphic
    val coverGalleryLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.GetContent()
    ) { uri: Uri? ->
        uri?.let {
            startCrop(it, ImageType.COVER_GRAPHIC)
        }
    }
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Layers,
                            contentDescription = "ShareMyCard Logo",
                            modifier = Modifier.size(24.dp),
                            tint = MaterialTheme.colorScheme.primary
                        )
                        Text(if (uiState.isNewCard) "Create Card" else "Edit Card")
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    TextButton(
                        onClick = { viewModel.saveCard() },
                        enabled = !uiState.isSaving
                    ) {
                        if (uiState.isSaving) {
                            CircularProgressIndicator(modifier = Modifier.size(16.dp))
                        } else {
                            Text("Save")
                        }
                    }
                }
            )
        }
    ) { paddingValues ->
        if (uiState.isLoading) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator()
            }
        } else {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
                    .verticalScroll(rememberScrollState())
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Error message
                uiState.errorMessage?.let { error ->
                    Card(
                        colors = CardDefaults.cardColors(
                            containerColor = MaterialTheme.colorScheme.errorContainer
                        ),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text(
                            text = error,
                            color = MaterialTheme.colorScheme.onErrorContainer,
                            modifier = Modifier.padding(16.dp)
                        )
                    }
                }
                
                // Required Information Section
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        Text(
                            text = "Required Information",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold
                        )
                        
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            OutlinedTextField(
                                value = uiState.firstName,
                                onValueChange = { viewModel.updateFirstName(it) },
                                label = { Text("First Name") },
                                modifier = Modifier.weight(1f),
                                singleLine = true
                            )
                            OutlinedTextField(
                                value = uiState.lastName,
                                onValueChange = { viewModel.updateLastName(it) },
                                label = { Text("Last Name") },
                                modifier = Modifier.weight(1f),
                                singleLine = true
                            )
                        }
                        
                        OutlinedTextField(
                            value = uiState.phoneNumber,
                            onValueChange = { viewModel.updatePhoneNumber(it) },
                            label = { Text("Phone Number") },
                            modifier = Modifier.fillMaxWidth(),
                            singleLine = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone)
                        )
                    }
                }
                
                // Professional Information Section
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        Text(
                            text = "Professional Information",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold
                        )
                        
                        OutlinedTextField(
                            value = uiState.companyName,
                            onValueChange = { viewModel.updateCompanyName(it) },
                            label = { Text("Company Name") },
                            modifier = Modifier.fillMaxWidth(),
                            singleLine = true
                        )
                        
                        OutlinedTextField(
                            value = uiState.jobTitle,
                            onValueChange = { viewModel.updateJobTitle(it) },
                            label = { Text("Job Title") },
                            modifier = Modifier.fillMaxWidth(),
                            singleLine = true
                        )
                        
                        OutlinedTextField(
                            value = uiState.bio,
                            onValueChange = { viewModel.updateBio(it) },
                            label = { Text("Bio") },
                            modifier = Modifier.fillMaxWidth(),
                            minLines = 3,
                            maxLines = 5
                        )
                    }
                }
                
                // Media Section
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        Text(
                            text = "Media",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold
                        )
                        
                        // Profile Photo
                        ImagePickerSection(
                            title = "Profile Photo",
                            dimensions = "Recommended: 1000x1000px (1:1 square)",
                            imageBitmap = uiState.profilePhotoBitmap,
                            imagePath = uiState.profilePhotoPath,
                            onGalleryClick = { profilePhotoGalleryLauncher.launch("image/*") },
                            onRemoveClick = { viewModel.removeProfilePhoto() }
                        )
                        
                        // Company Logo
                        ImagePickerSection(
                            title = "Company Logo",
                            dimensions = "Recommended: 800x600px (4:3) or 1000x1000px (1:1 square)",
                            imageBitmap = uiState.companyLogoBitmap,
                            imagePath = uiState.companyLogoPath,
                            onGalleryClick = { logoGalleryLauncher.launch("image/*") },
                            onRemoveClick = { viewModel.removeCompanyLogo() }
                        )
                        
                        // Cover Graphic
                        ImagePickerSection(
                            title = "Cover Graphic",
                            dimensions = "Recommended: 1920x1080px (16:9) or 1200x675px (16:9)",
                            imageBitmap = uiState.coverGraphicBitmap,
                            imagePath = uiState.coverGraphicPath,
                            onGalleryClick = { coverGalleryLauncher.launch("image/*") },
                            onRemoveClick = { viewModel.removeCoverGraphic() }
                        )
                    }
                }
                
                // Additional Emails Section
                var showEmailDialog by remember { mutableStateOf(false) }
                ContactListSection(
                    title = "Additional Emails",
                    items = uiState.additionalEmails,
                    onAddClick = { showEmailDialog = true },
                    onRemoveClick = { email -> viewModel.removeEmail(email.id) },
                    itemDisplay = { email -> "${email.email} (${email.type.name.lowercase()})" }
                )
                
                // Additional Phones Section
                var showPhoneDialog by remember { mutableStateOf(false) }
                ContactListSection(
                    title = "Additional Phones",
                    items = uiState.additionalPhones,
                    onAddClick = { showPhoneDialog = true },
                    onRemoveClick = { phone -> viewModel.removePhone(phone.id) },
                    itemDisplay = { phone -> "${phone.phoneNumber} (${phone.type.name.lowercase()})" }
                )
                
                // Website Links Section
                var showWebsiteDialog by remember { mutableStateOf(false) }
                ContactListSection(
                    title = "Website Links",
                    items = uiState.websiteLinks,
                    onAddClick = { showWebsiteDialog = true },
                    onRemoveClick = { website -> viewModel.removeWebsite(website.id) },
                    itemDisplay = { website -> "${website.url}${if (website.name.isNotBlank()) " - ${website.name}" else ""}" }
                )
                
                // Dialogs
                if (showEmailDialog) {
                    AddEmailDialog(
                        onDismiss = { showEmailDialog = false },
                        onAdd = { email ->
                            viewModel.addEmail(email)
                            showEmailDialog = false
                        }
                    )
                }
                
                if (showPhoneDialog) {
                    AddPhoneDialog(
                        onDismiss = { showPhoneDialog = false },
                        onAdd = { phone ->
                            viewModel.addPhone(phone)
                            showPhoneDialog = false
                        }
                    )
                }
                
                if (showWebsiteDialog) {
                    AddWebsiteDialog(
                        onDismiss = { showWebsiteDialog = false },
                        onAdd = { website ->
                            viewModel.addWebsite(website)
                            showWebsiteDialog = false
                        }
                    )
                }
                
                // Address Section
                AddressSection(
                    address = uiState.address,
                    onStreetChange = { viewModel.updateStreet(it) },
                    onCityChange = { viewModel.updateCity(it) },
                    onStateChange = { viewModel.updateState(it) },
                    onZipCodeChange = { viewModel.updateZipCode(it) },
                    onCountryChange = { viewModel.updateCountry(it) }
                )
                
                // Theme Section
                ThemeSelectorSection(
                    selectedTheme = uiState.theme,
                    onThemeSelected = { viewModel.updateTheme(it) }
                )
                
                // Status Section
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(
                            text = "Active",
                            style = MaterialTheme.typography.bodyLarge
                        )
                        Switch(
                            checked = uiState.isActive,
                            onCheckedChange = { viewModel.updateIsActive(it) }
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun ImagePickerSection(
    title: String,
    dimensions: String,
    imageBitmap: Bitmap?,
    imagePath: String?,
    onGalleryClick: () -> Unit,
    onRemoveClick: (() -> Unit)? = null
) {
    Column(
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Column(
            verticalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            Text(
                text = title,
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = FontWeight.Medium
            )
            Text(
                text = dimensions,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Image preview
            Box(
                modifier = Modifier
                    .size(80.dp)
                    .clip(RoundedCornerShape(8.dp)),
                contentAlignment = Alignment.Center
            ) {
                when {
                    imageBitmap != null -> {
                        Image(
                            bitmap = imageBitmap.asImageBitmap(),
                            contentDescription = title,
                            modifier = Modifier.fillMaxSize(),
                            contentScale = ContentScale.Crop
                        )
                    }
                    !imagePath.isNullOrBlank() -> {
                        val imageUrl = if (imagePath.startsWith("http")) {
                            imagePath
                        } else {
                            "https://sharemycard.app/api/media/view?file=$imagePath"
                        }
                        AsyncImage(
                            model = imageUrl,
                            contentDescription = title,
                            modifier = Modifier.fillMaxSize(),
                            contentScale = ContentScale.Crop
                        )
                    }
                    else -> {
                        Surface(
                            color = MaterialTheme.colorScheme.surfaceVariant,
                            modifier = Modifier.fillMaxSize()
                        ) {
                            Icon(
                                imageVector = Icons.Default.AddPhotoAlternate,
                                contentDescription = "Add $title",
                                modifier = Modifier.size(32.dp),
                                tint = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }
            }
            
            Column(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                TextButton(onClick = onGalleryClick) {
                    Icon(Icons.Default.PhotoLibrary, contentDescription = null)
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("Upload from Gallery")
                }
                
                // Show remove button only if there's an image
                if ((imageBitmap != null || !imagePath.isNullOrBlank()) && onRemoveClick != null) {
                    TextButton(
                        onClick = onRemoveClick,
                        colors = ButtonDefaults.textButtonColors(
                            contentColor = MaterialTheme.colorScheme.error
                        )
                    ) {
                        Icon(Icons.Default.Delete, contentDescription = null)
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Remove")
                    }
                }
            }
        }
    }
}

@Composable
fun <T> ContactListSection(
    title: String,
    items: List<T>,
    onAddClick: () -> Unit,
    onRemoveClick: (T) -> Unit,
    itemDisplay: (T) -> String
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = title,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
                IconButton(onClick = onAddClick) {
                    Icon(Icons.Default.Add, contentDescription = "Add")
                }
            }
            
            if (items.isEmpty()) {
                Text(
                    text = "No items added",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            } else {
                items.forEach { item ->
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(
                            text = itemDisplay(item),
                            modifier = Modifier.weight(1f)
                        )
                        IconButton(onClick = { onRemoveClick(item) }) {
                            Icon(
                                Icons.Default.Delete,
                                contentDescription = "Remove",
                                tint = MaterialTheme.colorScheme.error
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun AddressSection(
    address: Address?,
    onStreetChange: (String) -> Unit,
    onCityChange: (String) -> Unit,
    onStateChange: (String) -> Unit,
    onZipCodeChange: (String) -> Unit,
    onCountryChange: (String) -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(
                text = "Address",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )
            
            OutlinedTextField(
                value = address?.street ?: "",
                onValueChange = onStreetChange,
                label = { Text("Street") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true
            )
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                OutlinedTextField(
                    value = address?.city ?: "",
                    onValueChange = onCityChange,
                    label = { Text("City") },
                    modifier = Modifier.weight(2f),
                    singleLine = true
                )
                OutlinedTextField(
                    value = address?.state ?: "",
                    onValueChange = onStateChange,
                    label = { Text("State") },
                    modifier = Modifier.weight(1f),
                    singleLine = true
                )
            }
            
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                OutlinedTextField(
                    value = address?.zipCode ?: "",
                    onValueChange = onZipCodeChange,
                    label = { Text("ZIP Code") },
                    modifier = Modifier.weight(1f),
                    singleLine = true
                )
                OutlinedTextField(
                    value = address?.country ?: "",
                    onValueChange = onCountryChange,
                    label = { Text("Country") },
                    modifier = Modifier.weight(1f),
                    singleLine = true
                )
            }
        }
    }
}

@Composable
fun ThemeSelectorSection(
    selectedTheme: String,
    onThemeSelected: (String) -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(
                text = "Card Theme",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )
            
            LazyRow(
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                items(CardThemes.themes) { theme ->
                    val isSelected = theme.id == selectedTheme
                    
                    Column(
                        modifier = Modifier
                            .clickable { 
                                onThemeSelected(theme.id) 
                            }
                            .padding(8.dp),
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        Box(
                            modifier = Modifier
                                .size(60.dp)
                                .clip(RoundedCornerShape(8.dp))
                                .then(
                                    if (isSelected) {
                                        Modifier.border(
                                            3.dp,
                                            MaterialTheme.colorScheme.primary,
                                            RoundedCornerShape(8.dp)
                                        )
                                    } else {
                                        Modifier.border(
                                            1.dp,
                                            MaterialTheme.colorScheme.outline.copy(alpha = 0.3f),
                                            RoundedCornerShape(8.dp)
                                        )
                                    }
                                ),
                            contentAlignment = Alignment.Center
                        ) {
                            // Simple gradient representation
                            Surface(
                                color = androidx.compose.ui.graphics.Color(
                                    android.graphics.Color.parseColor(theme.primaryColor)
                                ),
                                modifier = Modifier.fillMaxSize()
                            ) {}
                            // Checkmark icon for selected theme
                            if (isSelected) {
                                Box(
                                    modifier = Modifier
                                        .size(24.dp)
                                        .clip(CircleShape)
                                        .background(MaterialTheme.colorScheme.primary),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Icon(
                                        imageVector = Icons.Default.Check,
                                        contentDescription = "Selected",
                                        tint = MaterialTheme.colorScheme.onPrimary,
                                        modifier = Modifier.size(16.dp)
                                    )
                                }
                            }
                        }
                        Text(
                            text = theme.name,
                            style = MaterialTheme.typography.labelSmall,
                            maxLines = 1,
                            fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal,
                            color = if (isSelected) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurface
                        )
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AddEmailDialog(
    onDismiss: () -> Unit,
    onAdd: (EmailContact) -> Unit
) {
    var email by remember { mutableStateOf("") }
    var emailType by remember { mutableStateOf(EmailType.WORK) }
    var label by remember { mutableStateOf("") }
    var isPrimary by remember { mutableStateOf(false) }
    
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Add Email") },
        text = {
            Column(
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                OutlinedTextField(
                    value = email,
                    onValueChange = { email = it },
                    label = { Text("Email Address") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email)
                )
                
                var expanded by remember { mutableStateOf(false) }
                ExposedDropdownMenuBox(
                    expanded = expanded,
                    onExpandedChange = { expanded = !expanded }
                ) {
                    OutlinedTextField(
                        value = emailType.name,
                        onValueChange = {},
                        readOnly = true,
                        label = { Text("Type") },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                        modifier = Modifier
                            .fillMaxWidth()
                            .menuAnchor()
                    )
                    ExposedDropdownMenu(
                        expanded = expanded,
                        onDismissRequest = { expanded = false }
                    ) {
                        EmailType.values().forEach { type ->
                            DropdownMenuItem(
                                text = { Text(type.name) },
                                onClick = {
                                    emailType = type
                                    expanded = false
                                }
                            )
                        }
                    }
                }
                
                OutlinedTextField(
                    value = label,
                    onValueChange = { label = it },
                    label = { Text("Label (optional)") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true
                )
                
                Row(
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Checkbox(
                        checked = isPrimary,
                        onCheckedChange = { isPrimary = it }
                    )
                    Text("Set as primary email")
                }
            }
        },
        confirmButton = {
            TextButton(
                onClick = {
                    if (email.isNotBlank()) {
                        onAdd(EmailContact(
                            id = UUID.randomUUID().toString(),
                            email = email,
                            type = emailType,
                            label = label.takeIf { it.isNotBlank() },
                            isPrimary = isPrimary
                        ))
                    }
                },
                enabled = email.isNotBlank()
            ) {
                Text("Add")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancel")
            }
        }
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AddPhoneDialog(
    onDismiss: () -> Unit,
    onAdd: (PhoneContact) -> Unit
) {
    var phoneNumber by remember { mutableStateOf("") }
    var phoneType by remember { mutableStateOf(PhoneType.MOBILE) }
    var label by remember { mutableStateOf("") }
    
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Add Phone") },
        text = {
            Column(
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                OutlinedTextField(
                    value = phoneNumber,
                    onValueChange = { phoneNumber = it },
                    label = { Text("Phone Number") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone)
                )
                
                var expanded by remember { mutableStateOf(false) }
                ExposedDropdownMenuBox(
                    expanded = expanded,
                    onExpandedChange = { expanded = !expanded }
                ) {
                    OutlinedTextField(
                        value = phoneType.name,
                        onValueChange = {},
                        readOnly = true,
                        label = { Text("Type") },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
                        modifier = Modifier
                            .fillMaxWidth()
                            .menuAnchor()
                    )
                    ExposedDropdownMenu(
                        expanded = expanded,
                        onDismissRequest = { expanded = false }
                    ) {
                        PhoneType.values().forEach { type ->
                            DropdownMenuItem(
                                text = { Text(type.name) },
                                onClick = {
                                    phoneType = type
                                    expanded = false
                                }
                            )
                        }
                    }
                }
                
                OutlinedTextField(
                    value = label,
                    onValueChange = { label = it },
                    label = { Text("Label (optional)") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true
                )
            }
        },
        confirmButton = {
            TextButton(
                onClick = {
                    if (phoneNumber.isNotBlank()) {
                        onAdd(PhoneContact(
                            id = UUID.randomUUID().toString(),
                            phoneNumber = phoneNumber,
                            type = phoneType,
                            label = label.takeIf { it.isNotBlank() }
                        ))
                    }
                },
                enabled = phoneNumber.isNotBlank()
            ) {
                Text("Add")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancel")
            }
        }
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AddWebsiteDialog(
    onDismiss: () -> Unit,
    onAdd: (WebsiteLink) -> Unit
) {
    var url by remember { mutableStateOf("") }
    var name by remember { mutableStateOf("") }
    var description by remember { mutableStateOf("") }
    var isPrimary by remember { mutableStateOf(false) }
    
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Add Website") },
        text = {
            Column(
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                OutlinedTextField(
                    value = url,
                    onValueChange = { url = it },
                    label = { Text("URL") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Uri)
                )
                
                OutlinedTextField(
                    value = name,
                    onValueChange = { name = it },
                    label = { Text("Name (optional)") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true
                )
                
                OutlinedTextField(
                    value = description,
                    onValueChange = { description = it },
                    label = { Text("Description (optional)") },
                    modifier = Modifier.fillMaxWidth(),
                    minLines = 2,
                    maxLines = 3
                )
                
                Row(
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Checkbox(
                        checked = isPrimary,
                        onCheckedChange = { isPrimary = it }
                    )
                    Text("Set as primary website")
                }
            }
        },
        confirmButton = {
            TextButton(
                onClick = {
                    if (url.isNotBlank()) {
                        onAdd(WebsiteLink(
                            id = UUID.randomUUID().toString(),
                            url = url,
                            name = name.ifBlank { "" },
                            description = description.takeIf { it.isNotBlank() },
                            isPrimary = isPrimary
                        ))
                    }
                },
                enabled = url.isNotBlank()
            ) {
                Text("Add")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancel")
            }
        }
    )
}

