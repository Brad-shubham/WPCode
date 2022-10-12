/**
 * WordPress dependencies
 */
import axios, * as others from 'axios';
import blockJson from '../block.json'

const {registerBlockType} = wp.blocks;
const {
    RichText,
    useBlockProps
} = wp.blockEditor;
const {
    SelectControl,
} = wp.components;
const { Component } = wp.element;

//import {useBlockProps} from '@wordpress/block-editor';


// Register the block
registerBlockType(blockJson.name, {
    attributes: {
        postData: [],
        categories: [],
        textAtt: {
            type: 'string',
            source: 'html',
        },
        postType: {
            type: 'string'
        },
    },
    supports: {
        align: ['wide', 'full']
    },
    edit: (props)=>{
        const {attributes, setAttributes} = props;
        const blockProps = useBlockProps();
        let getPosts = async () => {
            await axios.get('http://wp-advance.test/wp-json/wp/v2/posts')
                .then((res) => {
                    setAttributes({postData: res.data})
                })
        }

        let getMedia = async (id=null) => {
            return  await axios.get('http://wp-advance.test/wp-json/wp/v2/media/'+id)
        }

        return (<div {...blockProps} >
            <SelectControl
                label="Choose post type..."
                value={attributes.postType}
                options={[
                    {label: "Dogs", value: 'dogs'},
                    {label: "Cats", value: 'cats'},
                    {label: "Something else", value: 'weird_one'},
                ]}
                onChange={async (thisSelected) => {
                    setAttributes({postType: thisSelected})
                    console.log(attributes.postType, thisSelected)
                    await getPosts()
                }}
            />
            <ul className="gp-block-post-list">{attributes.postData?.map((temp) => {
                let media =  getMedia(temp.featured_media).then((res)=>{
                    return res.data.source_url
                })
                    return (<li>
                        <div className="gp-block-card">
                            <div className="gp-featured">
                                <img src={media} alt="feature"/>
                            </div>
                            <div className="gp-block-post-title">
                                <h2>{temp.title.rendered}</h2>
                            </div>
                        </div>
                    </li>)
            })}</ul>

        </div>)
    },
    save: (props) => {
        const {attributes} = props;
       // const {...blockProps} = useBlockProps.save({style: greenBackground, className: 'test-class'});
        return (<div>
            <ul>{attributes.postData?.map((temp) => {
                return (<li>
                    <div className="gp-block-card">
                        <div className="gp-featured">
                            <img src="" alt="feature"/>
                        </div>
                    </div>
                </li>)
            })}</ul>
        </div>)

    },
});